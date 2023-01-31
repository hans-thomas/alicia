<?php


	namespace Hans\Alicia\Models;


	use Hans\Alicia\Contracts\SignatureContract;
	use Hans\Alicia\Traits\FFMpegPreConfig;
	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Database\Eloquent\Casts\Attribute;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Facades\App;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\URL;
	use Symfony\Component\HttpFoundation\BinaryFileResponse;

	class Resource extends Model {
		use FFMpegPreConfig;

		protected $fillable = [
			'title',
			'path',
			'file',
			'hls',
			'link',
			'extension',
			'options',
			'external',
			'published_at'
		];
		protected $casts = [
			'options'  => 'array',
			'external' => 'boolean'
		];
		private array $configuration;
		private Filesystem $storage;

		public function __construct( array $attributes = [] ) {
			parent::__construct( $attributes );
			$this->configuration = config( 'alicia' );
			$this->storage       = Storage::disk( 'resources' );
		}

		protected static function booted() {
			if ( config( 'alicia.onlyPublishedFiles' ) ) {
				//self::addGlobalScope( new PublishedOnlyScope() );
			}
		}

		public function url(): Attribute {
			return new Attribute( get: function() {
				if ( $this->isExternal() ) {
					return $this->link;
				}

				if ( $this->getConfig( 'signed' ) ) {
					return URL::temporarySignedRoute( 'alicia.download',
						now()->addMinutes( $this->getConfig( 'expiration' ) ), [
							'resource' => $this->id,
							'hash'     => App::make( SignatureContract::class )->create()
						] );
				} else {
					return route( 'alicia.download', [ 'resource' => $this->id ] );
				}
			} );
		}

		public function hlsUrl(): Attribute {
			return new Attribute( get: function() {
				if ( ! $this->isPublished() ) {
					return null;
				}
				if ( in_array( $this->extension, $this->getConfig( 'extensions.audios' ) ) ) {
					$response = new BinaryFileResponse( $this->storage->path( $this->address ) );
					BinaryFileResponse::trustXSendfileTypeHeader();

					return $response;
				}
				if ( $this->getConfig( 'hls.enable' ) ) {
					return url( 'resources/' . $this->path . '/' . $this->hls );
				} else {
					return url( 'resources/' . $this->path . '/' . $this->file );
				}
			} );
		}

		public function address(): Attribute {
			return new Attribute( get: fn() => $this->path . '/' . $this->file );
		}

		public function storagePath(): Attribute {
			return new Attribute( get: fn() => $this->storage->path( $this->address ) );
		}

		public function isExternal(): bool {
			return $this->external;
		}

		private function getConfig( string $key ) {
			return Arr::get( $this->configuration, $key );
		}

		public function isPublished(): bool {
			return $this->published_at != null;
		}

		public function status(): string {
			return $this->isPublished() ? 'published' : 'waiting';
		}

		public function setOptions( array $options ) {
			return $this->update( [ 'options' => array_merge( $this->getOptions(), $options ) ] );
		}

		public function getOptions() {
			return $this->options;
		}

		public function parent(): BelongsTo {
			return $this->belongsTo( self::class, 'parent_id' );
		}

		public function children(): HasMany {
			return $this->hasMany( self::class, 'parent_id' );
		}
	}
