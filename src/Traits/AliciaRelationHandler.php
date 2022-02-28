<?php


	namespace Hans\Alicia\Traits;


	use Hans\Alicia\Contracts\AliciaContract;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use function app;

	trait AliciaRelationHandler {
		public function removeUploads(): array {
			$ids = $this->uploads->pluck( 'id' );
			$this->uploads()->detach( $ids );

			return app( AliciaContract::class )->batchDelete( $ids );
		}

		public function uploads() {
			return $this->morphToMany( ResourceModel::class, 'resourcable' );
		}
	}
