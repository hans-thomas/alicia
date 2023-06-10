<?php


	namespace Hans\Alicia\Services;


	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Hans\Alicia\Services\Actions\BatchUpload;
	use Hans\Alicia\Services\Actions\Delete;
	use Hans\Alicia\Services\Actions\Export;
	use Hans\Alicia\Services\Actions\External;
	use Hans\Alicia\Services\Actions\FromFile;
	use Hans\Alicia\Services\Actions\MakeExternal;
	use Hans\Alicia\Services\Actions\Upload;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Collection;

	class AliciaService {

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
		 */
		public function batch( array $files ): self {
			$this->data = ( new BatchUpload( $files ) )->run();

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
			$this->data->push( ( new Upload( $file ) )->run() );

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
			$this->data->push( ( new External( $file ) )->run() );

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
		 * @param Resource $model
		 * @param string   $url
		 *
		 * @return $this
		 * @throws AliciaException
		 */
		public function makeExternal( Resource $model, string $url ): self {
			$this->data->push( ( new MakeExternal( $model, $url ) )->run() );

			return $this;
		}

		/**
		 * Validate the request and upload the file
		 *
		 * @param string $path
		 *
		 * @return self
		 * @throws AliciaException ()
		 */
		public function fromFile( string $path ): self {
			$this->data->push( ( new FromFile( $path ) )->run() );

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
			$model = $model instanceof Resource ?
				$model :
				Resource::query()
				        ->select( [ 'id', 'directory', 'external' ] )
				        ->findOrFail( $model );

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
				$key = $id instanceof Resource ?
					$id->id :
					$id;
				$results->put( $key, $this->delete( $id ) );
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

		/**
		 * Return created Model(s)
		 *
		 * @return Resource|Collection|null
		 */
		public function getData(): Resource|Collection|null {
			if ( $this->data->isEmpty() ) {
				return null;
			}

			$result = $this->data->count() == 1 ?
				$this->data->first() :
				$this->data;

			$this->data = collect();

			return $result;
		}

	}
