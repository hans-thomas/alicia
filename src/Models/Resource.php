<?php


	namespace Hans\Alicia\Models;


	use Hans\Alicia\Contracts\SignatureContract;
	use Hans\Alicia\Scopes\PublishedOnlyScope;
	use Hans\Alicia\Traits\FFMpegPreConfig;
	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Facades\App;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\URL;
	use Symfony\Component\HttpFoundation\BinaryFileResponse;

	class Resource extends Model {
		use FFMpegPreConfig;

		protected $fillable = [ 'title', 'path', 'file', 'hls', 'extension', 'options', 'external', 'published_at' ];
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
				self::addGlobalScope( new PublishedOnlyScope() );
			}
		}

		public function getUrlAttribute() {
			if ( $this->isExternal() ) {
				return $this->path;
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

		}

		public function isExternal(): bool {
			return $this->external;
		}

		private function getConfig( string $key ) {
			return Arr::get( $this->configuration, $key );
		}

		public function getHlsUrlAttribute() {
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
		}

		public function isPublished(): bool {
			return $this->published_at != null;
		}

		public function getAddressAttribute() {
			return $this->path . '/' . $this->file;
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
	}
