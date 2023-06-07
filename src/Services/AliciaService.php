<?php


	namespace Hans\Alicia\Services;


	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Hans\Alicia\Services\Actions\Export;
	use Hans\Alicia\Services\Actions\External;
	use Hans\Alicia\Services\Actions\Upload;
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
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;
	use Throwable;

	class AliciaService {
		use Utils;

		private Filesystem $storage;
		private Resource $model;
		private Collection $data;

		public function __construct() {
			$this->data = collect();
		}

		/**
		 * Store the given files and links
		 *
		 * @param array $files
		 *
		 * @return self
		 * @throws AliciaException
		 */
		public function batch( array $files ): self {
			if ( empty( $files ) ) {
				throw new AliciaException(
					'Empty request! the passed files array is empty.',
					AliciaErrorCode::KEY_IS_NULL,
					ResponseAlias::HTTP_BAD_REQUEST
				);
			}
			foreach ( $files as $file ) {
				if ( $file instanceof UploadedFile ) {
					$this->data->push( $this->upload( $file )->getModel() );
				} elseif ( is_string( $file ) ) {
					$this->data->push( $this->external( $file )->getModel() );
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
			$this->model = ( new Upload( $file ) )->run();

			return $this;
		}

		/**
		 * Store a given external link
		 *
		 * @param string $file
		 *
		 * @return $this
		 * @throws AliciaException ()
		 */
		public function external( string $file ): self {
			$this->model = ( new External( $file ) )->run();

			return $this;
		}

		/**
		 * Create the folder to store the uploaded file
		 *
		 * @return string
		 */
		public function generateFolder(): string {
			if ( ! alicia_storage()->exists( $folder = alicia_config( 'classification' ) ) ) {
				alicia_storage()->makeDirectory( $folder );
			}

			return ltrim( $folder, '/' );
		}

		/**
		 * Delete a specific file
		 *
		 * @param string $path
		 *
		 * @return bool
		 */
		public function deleteFile( string $path ): bool {
			if ( alicia_storage()->exists( $path ) ) {
				return alicia_storage()->delete( $path );
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
		 * @throws AliciaException
		 */
		public function delete( Resource|int $model ): bool {
			$model = $model instanceof Resource ? $model : Resource::findOrFail( $model );
			DB::beginTransaction();
			try {
				if ( ! $model->isExternal() and alicia_storage()->exists( $model->path ) ) {
					alicia_storage()->deleteDirectory( $model->path );
				}
				if ( $model->children()->exists() ) {
					foreach ( $model->children as $child ) {
						$this->delete( $child );
					}
				}
				$model->delete();
			} catch ( Throwable $e ) {
				DB::rollBack();

				throw new AliciaException(
					'Failed to delete resource! ' . $e->getMessage(),
					AliciaErrorCode::FAILED_TO_DELETE_RESOURCE_MODEL
				);
			}
			DB::commit();

			return true;
		}

		/**
		 * Return created Model(s)
		 *
		 * @return Resource|Collection
		 */
		public function getData(): Resource|Collection {
			return match ( $this->data->isEmpty() ) {
				true => $this->getModel(),
				default => $this->data,
			};
		}

		/**
		 * @throws AliciaException
		 */
		public function export( array $resolutions = null ): self {
			if ( ! ( alicia_config( 'export' ) or $resolutions ) ) {
				throw new AliciaException( 'No resolution sets for exporting!', AliciaErrorCode::EXPORT_CONFIG_NOT_SET,
					ResponseAlias::HTTP_INTERNAL_SERVER_ERROR );
			}
			$exports = collect( $data = Arr::wrap( $this->getData() ) );
			foreach ( $data as $model ) {
				$exports->push( ( new Export( $model, $resolutions ) )->run()->toArray() );
			}

			$this->data = $exports->flatten( 1 )
			                      ->groupBy( fn( $item, $key ) => $item[ 'parent_id' ] ? $item[ 'parent_id' ] . '-children' : 'parents' );

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
				$this->model = $this->makeModel( [
					'title'     => Str::beforeLast( $file, '.' ),
					'path'      => $this->generateFolder() . '/' . $this->generateName( 'string', 8 ),
					'file'      => $this->generateName() . '.' . $extension,
					'extension' => $extension,
					'options'   => $options,
				] );
				alicia_storage()->put( $this->model->path . '/' . $this->model->file, $fs->read( $file ) );
				DB::commit();
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException(
					'Making resource from file failed! ' . $e->getMessage(),
					AliciaErrorCode::UPLOAD_FAILED
				);
			}

			return $this;
		}

	}
