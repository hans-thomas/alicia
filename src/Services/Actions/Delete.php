<?php

	namespace Hans\Alicia\Services\Actions;

	use Hans\Alicia\Contracts\Actions;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Illuminate\Support\Facades\DB;
	use Throwable;

	class Delete extends Actions {

		public function __construct(
			protected readonly Resource $model
		) {
		}

		public function run(): Resource {
			DB::beginTransaction();
			try {
				if ( $this->model->children()->exists() ) {
					foreach ( $this->model->children()->select( 'id', 'path', 'external' )->get() as $child ) {
						( new self( $child ) )->run();
					}
				}
				$this->model->delete();
				if ( ! $this->model->isExternal() and alicia_storage()->exists( $this->model->path ) ) {
					alicia_storage()->deleteDirectory( $this->model->path );
				}
			} catch ( Throwable $e ) {
				DB::rollBack();

				throw new AliciaException(
					'Failed to delete resource! ' . $e->getMessage(),
					AliciaErrorCode::FAILED_TO_DELETE_RESOURCE_MODEL
				);
			}
			DB::commit();

			return $this->model;
		}
	}