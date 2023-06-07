<?php


	namespace Hans\Alicia\Services;


	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Hans\Alicia\Services\Actions\Delete;
	use Hans\Alicia\Services\Actions\Export;
	use Hans\Alicia\Services\Actions\External;
	use Hans\Alicia\Services\Actions\MakeExternal;
	use Hans\Alicia\Services\Actions\Upload;
	use Illuminate\Contracts\Filesystem\FileNotFoundException;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;
	use Throwable;

	class AliciaService {

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
			// TODO: remove exception
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
		 * @throws AliciaException
		 */
		public function export( array $resolutions = null ): self {
			$exports = collect( $data = Arr::wrap( $this->getData() ) );
			foreach ( $data as $model ) {
				$exports->push( ( new Export( $model, $resolutions ) )->run()->toArray() );
			}

			$this->data = $exports->flatten( 1 )
			                      ->groupBy(
				                      fn( $item, $key ) => $item[ 'parent_id' ] ?
					                      $item[ 'parent_id' ] . '-children' :
					                      'parents'
			                      );

			return $this;
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
			$model = $model instanceof Resource ? $model : Resource::query()->findOrFail( $model );

			( new Delete( $model ) )->run();

			return true;
		}

		/**
		 * Delete resources in batch mode
		 *
		 * @param array $ids
		 *
		 * @return array
		 * @throws AliciaException
		 */
		public function batchDelete( array $ids ): array {
			$results = collect();
			foreach ( $ids as $id ) {
				$results->put( $id, $this->delete( $id ) );
			}

			return $results->toArray();
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

		public function makeExternal( Resource $model, string $url ): self {
			if ( $model->isExternal() ) {
				throw new AliciaException(
					"Model is external already!",
					AliciaErrorCode::MODEL_IS_EXTERNAL_ALREADY
				);
			}

			( new MakeExternal( $model, $url ) )->run();

			return $this;
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

		/**
		 * Return created Model(s)
		 *
		 * @return Resource|Collection|null
		 */
		public function getData(): Resource|Collection|null {
			return match ( $this->data->isEmpty() ) {
				true => $this->getModel(),
				default => $this->data,
			};
		}


		/**
		 * Return created model instance
		 *
		 * @return Resource|null
		 */
		protected function getModel(): ?Resource {
			return $this->model->refresh() ?? null;
		}

	}
