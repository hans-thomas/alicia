<?php

	namespace Hans\Alicia\Services\Actions;

	use Hans\Alicia\Contracts\Actions;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Models\Resource;
	use Illuminate\Support\Facades\DB;
	use Throwable;

	class MakeExternal extends Actions {

		public function __construct(
			protected readonly Resource $model,
			protected readonly string $url,
		) {
		}

		public function run(): Resource {
			if ( $this->model->isExternal() ) {
				throw new AliciaException(
					"Model is external already!",
					AliciaErrorCode::MODEL_IS_EXTERNAL_ALREADY
				);
			}
			$address = $this->model->address;

			DB::beginTransaction();
			try {
				$this->model->update( [
					'path'     => null,
					'file'     => null,
					'link'     => $this->url,
					'external' => true,
				] );
				Alicia::deleteFile( $address );
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException(
					"Failed to make model external! " . $e->getMessage(),
					AliciaErrorCode::FAILED_TO_MAKE_MODEL_EXTERNAL
				);
			}
			DB::commit();

			return $this->model;
		}
	}