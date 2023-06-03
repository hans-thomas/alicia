<?php


	namespace Hans\Alicia\Services;


	use Hans\Alicia\Contracts\AliciaContract;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Hans\Alicia\Traits\Utils;
	use Illuminate\Contracts\Filesystem\FileNotFoundException;
	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Str;
	use Illuminate\Validation\ValidationException;
	use Spatie\Image\Exceptions\InvalidManipulation;
	use Spatie\Image\Image;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;
	use Throwable;

	class AliciaService implements AliciaContract {
		use Utils;

		private Filesystem $storage;
		private Resource $model;
		private Collection $data;

		public function __construct() {
			$this->storage = Storage::disk( 'resources' );
			$this->data    = collect();
		}

		/**
		 * Store the given files and links
		 *
		 * @param string     $field
		 * @param array|null $uploadRules
		 * @param array|null $externalRules
		 *
		 * @return $this
		 * @throws AliciaException ()
		 * @throws ValidationException
		 */
		public function batch( string $field, array $uploadRules = null, array $externalRules = null ): self {
			$request = $this->getRequest( $field );
			if ( empty( $request[ $field ] ) ) {
				throw new AliciaException( 'Empty request! the \'' . $field . '\' key is null.',
					AliciaErrorCode::KEY_IS_NULL, ResponseAlias::HTTP_BAD_REQUEST );
			}
			foreach ( Arr::first( $request ) as $key => $item ) {
				if ( $item instanceof UploadedFile ) {
					$this->data->push( $this->upload( $field . '\.' . $key, $uploadRules )->getModel() );
				} elseif ( is_string( $item ) ) {
					$this->data->push( $this->external( $field . '\.' . $key, $externalRules )->getModel() );
				}
			}

			return $this;
		}

		/**
		 * Validate the request and upload the file
		 *
		 * @param UploadedFile $file
		 *
		 * @return self
		 * @throws AliciaException ()
		 */
		public function upload( UploadedFile $file ): self {
			DB::beginTransaction();
			try {
				$this->makeModel( [
					'title'     => Str::of( $this->getFileName( $file ) )->camel()->snake()->toString(),
					'path'      => $this->generateFolder() . '/' . $this->generateName( 'string', 8 ),
					'file'      => $this->generateName() . '.' . $extension = $this->getExtension( $file ),
					'extension' => $extension,
					'options'   => $this->getOptions( $file ),
				] );
				$this->storeOnDisk( $file );
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException(
					'Upload failed! ' . $e->getMessage(),
					AliciaErrorCode::UPLOAD_FAILED
				);
			}
			DB::commit();
			if ( $this->model->exists ) {
				$this->processModel( $this->model );
			}

			return $this;
		}

		/**
		 * Create the folder to store the uploaded file
		 *
		 * @return string
		 */
		public function generateFolder(): string {
			$folder = alicia_config( 'temp' ) ? : alicia_config( 'classification' );
			if ( ! $this->storage->exists( $folder ) ) {
				$this->storage->makeDirectory( $folder );
			}

			return ltrim( $folder, '/' );
		}

		/**
		 * Generate a unique name according to the determined driver
		 *
		 * @param string|null $driver
		 * @param int         $length
		 *
		 * @return string
		 */
		public function generateName( string $driver = null, int $length = 16 ): string {
			return match ( $driver ? : alicia_config( 'naming' ) ) {
				'uuid' => Str::uuid(),
				'string' => Str::random( $length ),
				'digits' => substr( str_shuffle( '012345678901234567890123456789' ), 0, $length ),
				'string_digits' => substr( str_shuffle( '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' ),
					0, $length ),
				'symbols' => substr( str_shuffle( '!@#$%^&*(){}><?~' ), 0, $length ),
				'hash' => substr( bcrypt( time() ), 0, $length ),
				default => Str::uuid(),
			};

		}

		/**
		 * Store a given external link
		 *
		 * @param string     $field
		 * @param array|null $rules
		 *
		 * @return $this
		 * @throws AliciaException ()
		 * @throws ValidationException
		 */
		public function external( string $field, array $rules = null ): self {
			$this->validate( $field, $rules ? : [ 'required' ], 'external' );
			try {
				DB::beginTransaction();
				$this->model = $this->save( [
					'title'        => $this->setTitle( $field ),
					'link'         => $this->getFromRequest( $field ),
					'extension'    => $this->getExtension( $field ),
					'external'     => true,
					'published_at' => now()
				] );
				$this->model->refresh();
				DB::commit();
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException( 'External link store failed!', AliciaErrorCode::EXTERNAL_LINK_STORE_FAILED );
			}

			return $this;
		}

		/**
		 * Delete a specific file
		 *
		 * @param string $path
		 *
		 * @return bool
		 */
		public function deleteFile( string $path ): bool {
			if ( $this->storage->exists( $path ) ) {
				return $this->storage->delete( $path );
			}

			return false;
		}

		/**
		 * Delete resources in batch mode
		 *
		 * @param array $ids
		 *
		 * @return array
		 */
		public function batchDelete( array $ids ): array {
			$results = collect();
			foreach ( $ids as $id ) {
				$results->put( $id, $this->delete( $id ) );
			}

			return $results->toArray();
		}

		/**
		 * Delete a specific resource include source file, hls etc
		 *
		 * @param Resource|int $model
		 *
		 * @return bool
		 */
		public function delete( Resource|int $model ): bool {
			$model = $model instanceof Resource ? $model : Resource::findOrFail( $model );
			DB::beginTransaction();
			try {
				if ( ! $model->isExternal() and $this->storage->exists( $model->path ) ) {
					$this->storage->deleteDirectory( $model->path );
				}
				if ( $model->children()->exists() ) {
					foreach ( $model->children as $child ) {
						$this->delete( $child );
					}
				}
				$model->delete();
			} catch ( Throwable $e ) {
				DB::rollBack();

				return false;
			}
			DB::commit();

			return true;
		}

		/**
		 * Return created Model(s)
		 *
		 * @return Resource|Collection
		 * @throws AliciaException
		 */
		public function getData(): Resource|Collection {
			return match ( $this->data->isEmpty() ) {
				true => $this->getModel(),
				default => $this->data,
			};
		}

		/**
		 * @throws AliciaException
		 * @throws InvalidManipulation
		 */
		public function export( array $resolutions = null ): self {
			if ( ! ( alicia_config( 'export' ) or $resolutions ) ) {
				throw new AliciaException( 'No resolution sets for exporting!', AliciaErrorCode::EXPORT_CONFIG_NOT_SET,
					ResponseAlias::HTTP_INTERNAL_SERVER_ERROR );
			}
			$data = collect();
			foreach ( $this->getData() instanceof Collection ? $this->getData() : Arr::wrap( $this->getData() ) as $model ) {
				$data->push( $model );
				if ( $model->isExternal() or ! in_array( $model->extension,
						alicia_config( 'extensions.images' ) ) ) {
					continue;
				}
				foreach ( $resolutions ? : alicia_config( 'export' ) as $height => $width ) {
					$fileName = Str::remove( '.' . $model->extension,
							$model->file ) . "-{$height}x{$width}." . $model->extension;
					$filePath = $this->storage->path( $model->path . '/' . $fileName );
					Image::load( $this->storage->path( $model->address ) )
					     ->optimize()
					     ->height( $height )
					     ->width( $width )
					     ->save( $filePath );
					$child = $this->save( [
						'title'        => $model->title . "-{$height}x{$width}",
						'path'         => $model->path,
						'file'         => $fileName,
						'extension'    => $model->extension,
						'options'      => array_merge( $model->options,
							[ 'size' => filesize( $filePath ), 'width' => $width, 'height' => $height ] ),
						'external'     => $model->external,
						'published_at' => now()
					] );
					$child->parent()->associate( $model )->save();
					$data->push( $child->withoutRelations() );
				}
			}
			$this->data = $data->groupBy( fn( $item, $key ) => $item[ 'parent_id' ] ? $item[ 'parent_id' ] . '-children' : 'parents' );

			return $this;
		}

		public function makeExternal( Resource $resource, string $url, bool $deleteFile = false ): Resource {
			if ( $resource->isExternal() ) {
				return $resource;
			}

			$validator = Validator::make( [
				'url' => $url
			], [ 'url' => [ 'required', 'url' ] ] );

			if ( $validator->fails() ) {
				throw new AliciaException( 'url is not valid!', AliciaErrorCode::URL_IS_INVALID );
			}

			$address = $resource->address;

			DB::beginTransaction();
			try {
				$resource->update( [
					'link'     => $url,
					'external' => true,
				] );
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw $e;
			}
			DB::commit();

			if ( $deleteFile ) {
				$this->deleteFile( $address );
			}

			return $resource;
		}

		/**
		 * Validate the request and upload the file
		 *
		 * @param string $path
		 *
		 * @return $this
		 * @throws AliciaException ()
		 * @throws FileNotFoundException ()
		 */
		public function makeFromFile( string $path ): self {
			$fs        = Storage::build( Str::beforeLast( $path, '/' ) );
			$file      = Str::afterLast( $path, '/' );
			$extension = Str::afterLast( $path, '.' );
			if ( ! $fs->fileExists( $file ) ) {
				throw new FileNotFoundException( 'file not found!' );
			}
			$options[ 'size' ]     = $fs->size( $file );
			$options[ 'mimeType' ] = $fs->mimeType( $file );
			if ( $dimensions = getimagesize( $path ) ) {
				$options[ 'width' ]  = $dimensions[ 0 ];
				$options[ 'height' ] = $dimensions[ 1 ];
			}
			try {
				DB::beginTransaction();
				$this->model = $this->save( [
					'title'        => Str::beforeLast( $file, '.' ),
					'path'         => $this->generateFolder() . '/' . $this->generateName( 'string', 8 ),
					'file'         => $this->generateName() . '.' . $extension,
					'extension'    => $extension,
					'options'      => $options,
					'published_at' => now()
				] );
				$this->storage->put( $this->model->path . '/' . $this->model->file, $fs->read( $file ) );
				DB::commit();
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException( 'Making resource from file failed! ' . $e->getMessage(),
					AliciaErrorCode::UPLOAD_FAILED );
			}

			return $this;
		}

	}
