<?php

	namespace Hans\Alicia\Services\Actions;

	use Hans\Alicia\Contracts\Actions;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\DB;
	use Throwable;

	class Upload extends Actions {

		public function __construct(
			protected readonly UploadedFile $file
		) {
		}

		/**
		 * Contain action's logic
		 *
		 * @return ResourceModel
		 * @throws AliciaException
		 */
		public function run(): Resource {
			DB::beginTransaction();
			try {
				$model = $this->storeOnDB( [
					'title'     => $this->makeFileTitle( $this->file ),
					'directory' => get_classified_folder() . '/' . generate_file_name( 'string', 8 ),
					'file'      => generate_file_name() . '.' . $extension = $this->getExtension( $this->file ),
					'extension' => $extension,
					'options'   => $this->getOptions( $this->file ),
				] );
				$this->storeOnDisk( $model, $this->file );
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException(
					'Upload failed! ' . $e->getMessage(),
					AliciaErrorCode::UPLOAD_FAILED
				);
			}
			DB::commit();

			$this->processModel( $model );

			return $model->refresh();

		}

	}