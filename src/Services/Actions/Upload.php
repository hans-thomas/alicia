<?php

	namespace Hans\Alicia\Services\Actions;

	use Hans\Alicia\Contracts\Actions;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\DB;
	use Throwable;

	class Upload extends Actions {

		/**
		 * @throws AliciaException
		 */
		public function run( UploadedFile|string $file ): Resource {
			DB::beginTransaction();
			try {
				$this->model = $this->storeOnDB( [
					'title'     => $this->makeFileTitle( $file ),
					'path'      => get_classified_folder() . '/' . generate_file_name( 'string', 8 ),
					'file'      => generate_file_name() . '.' . $extension = $this->getExtension( $file ),
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

			$this->processModel( $this->model );

			return $this->model;

		}

	}