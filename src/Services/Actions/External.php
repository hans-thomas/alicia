<?php

	namespace Hans\Alicia\Services\Actions;

	use Hans\Alicia\Contracts\Actions;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\DB;
	use Throwable;

	class External extends Actions {

		public function run( string|UploadedFile $file ): Resource {
			$file = filter_var( $file, FILTER_SANITIZE_URL );
			$file = filter_var( $file, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			DB::beginTransaction();
			try {
				$this->model = $this->storeOnDB( [
					'title'     => $this->makeFileTitle( $file ),
					'link'      => $file,
					'extension' => $this->getExtension( $file ),
					'external'  => true,
				] );
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException(
					'External link store failed!',
					AliciaErrorCode::EXTERNAL_LINK_STORE_FAILED
				);
			}
			DB::commit();

			return $this->model;
		}
	}