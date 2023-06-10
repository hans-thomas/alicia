<?php


	namespace Hans\Alicia\Models;


	use Hans\Alicia\Facades\Signature;
	use Hans\Alicia\Traits\FFMpegPreConfig;
	use Illuminate\Database\Eloquent\Casts\Attribute;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Support\Facades\URL;
	use Symfony\Component\HttpFoundation\BinaryFileResponse;

	/**
	 * Fillables:
	 * @property int    $id
	 * @property string $title
	 * @property string $directory
	 * @property string $file
	 * @property string $hls
	 * @property string $link
	 * @property string $extension
	 * @property array  $options
	 * @property bool   $external
	 *
	 * Attributes:
	 * @property string $downloadUrl
	 * @property string $streamUrl
	 * @property string $address
	 * @property string $fullAddress
	 *
	 * Foreign keys:
	 * @property int    $parent_id
	 *
	 * @mixin Model
	 */
	class Resource extends Model {
		use FFMpegPreConfig;

		protected $fillable = [
			'title',
			'directory',
			'file',
			'hls',
			'link',
			'extension',
			'options',
			'external',
		];
		protected $casts = [
			'options'  => 'array',
			'external' => 'boolean'
		];

		public function downloadUrl(): Attribute {
			return new Attribute(
				get: function() {
					if ( alicia_config( 'signed' ) ) {
						return URL::temporarySignedRoute(
							'alicia.download',
							now()->addMinutes( alicia_config( 'expiration', 30 ) ),
							[
								'resource' => $this->id,
								'hash'     => Signature::create()
							]
						);
					} else {
						return route( 'alicia.download', [ 'resource' => $this->id ] );
					}
				}
			);
		}

		public function streamUrl(): Attribute {
			return new Attribute(
				get: function() {
					if ( in_array( $this->extension, alicia_config( 'extensions.audios' ) ) ) {
						$response = new BinaryFileResponse( alicia_storage()->path( $this->address ) );
						BinaryFileResponse::trustXSendfileTypeHeader();

						return $response;
					}

					if ( alicia_config( 'hls.enable' ) and $this->hls ) {
						return url( 'resources/' . $this->directory . '/' . $this->hls );
					} else {
						return url( 'resources/' . $this->directory . '/' . $this->file );
					}
				}
			);
		}

		public function address(): Attribute {
			// TODO: rename to path
			return new Attribute( get: fn() => $this->directory . '/' . $this->file );
		}

		public function fullAddress(): Attribute {
			// TODO: rename to fullPath
			return new Attribute( get: fn() => alicia_storage()->path( $this->address ) );
		}

		public function isExternal(): bool {
			return $this->external;
		}

		public function isNotExternal(): bool {
			return ! $this->isExternal();
		}

		public function updateOptions( array $options ) {
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
