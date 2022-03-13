<?php


	namespace Hans\Alicia;


	use Hans\Alicia\Contracts\AliciaContract;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Traits\Utils;
	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;
	use Illuminate\Validation\ValidationException;
	use Spatie\Image\Exceptions\InvalidManipulation;
	use Spatie\Image\Image;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;

	class AliciaService implements AliciaContract {
		use Utils;

		private Filesystem $storage;
		private ResourceModel $model;
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
		 * @param string     $field
		 * @param array|null $rules
		 *
		 * @return $this
		 * @throws AliciaException ()
		 * @throws ValidationException
		 */
		public function upload( string $field, array $rules = null ): self {
			$this->validate( $field, $rules ? : [
				'required',
				'mimes:' . $this->getAllowedExtensions(),
				'max:' . $this->getMaxSize( $field )
			] );

			try {
				DB::beginTransaction();
				$this->model = $this->save( [
					'title'     => $this->setTitle( $field ),
					'path'      => $this->generateFolder() . '/' . $this->generateName( 'string', 8 ),
					'file'      => $this->generateName() . '.' . $extension = $this->getExtension( $field ),
					'extension' => $extension,
					'options'   => $this->getOptions( $field )
				] );
				$this->storeFile( $this->getFromRequest( $field ) );
				$this->processModel( $this->model );
				DB::commit();
			} catch ( \Throwable $e ) {
				DB::rollBack();
				throw new AliciaException( 'Upload failed! ' . $e->getMessage(), AliciaErrorCode::UPLOAD_FAILED );
			}

			return $this;
		}

		/**
		 * Create the folder to store the uploaded file
		 *
		 * @return string
		 */
		public function generateFolder(): string {
			$folder = $this->getConfig( 'temp' ) ? : $this->getConfig( 'classification' );
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
			return match ( $driver ? : $this->getConfig( 'naming' ) ) {
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
					'path'         => $this->getFromRequest( $field ),
					'extension'    => $this->getExtension( $field ),
					'external'     => true,
					'published_at' => now()
				] );
				$this->model->refresh();
				DB::commit();
			} catch ( \Throwable $e ) {
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
		 * @param $id
		 *
		 * @return bool
		 */
		public function delete( $id ): bool {
			$model = ResourceModel::findOrFail( $id );
			try {
				if ( $this->storage->exists( $model->path ) ) {
					$this->storage->deleteDirectory( $model->path );
				}

				$model->delete();
			} catch ( \Throwable $e ) {
				return false;
			}

			return true;
		}

		/**
		 * Return created Model(s)
		 *
		 * @return ResourceModel|Collection
		 * @throws AliciaException
		 */
		public function getData(): ResourceModel|Collection {
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
			if ( ! ( $this->getConfig( 'export' ) or $resolutions ) ) {
				throw new AliciaException( 'No resolution sets for exporting!', AliciaErrorCode::EXPORT_CONFIG_NOT_SET,
					ResponseAlias::HTTP_INTERNAL_SERVER_ERROR );
			}
			$data = collect();
			foreach ( $this->getData() instanceof Collection ? $this->getData() : Arr::wrap( $this->getData() ) as $model ) {
				$data->push( $model );
				if ( $model->isExternal() or ! in_array( $model->extension,
						$this->getConfig( 'extensions.images' ) ) ) {
					continue;
				}
				foreach ( $resolutions ? : $this->getConfig( 'export' ) as $height => $width ) {
					$fileName = Str::remove( '.' . $model->extension,
							$model->file ) . "{$height}x{$width}." . $model->extension;
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
						'options'      => array_merge( $model->options, [ 'size' => filesize( $filePath ) ] ),
						'published_at' => now()
					] );
					$child->parent()->associate( $model )->save();
					$data->push( $child->withoutRelations() );
				}
			}
			$this->data = $data->groupBy( fn( $item, $key ) => $item[ 'parent_id' ] ? $item[ 'parent_id' ] . '-children' : 'parents' );

			return $this;
		}
	}
