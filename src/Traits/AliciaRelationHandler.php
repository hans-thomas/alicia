<?php


	namespace Hans\Alicia\Traits;


	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Models\Resource;

	trait AliciaRelationHandler {

		public function removeUploads(): array {
			$ids = $this->uploads()->select( 'id' )->pluck( 'id' );
			$this->uploads()->detach( $ids );

			return Alicia::batchDelete( $ids );
		}

		public function uploads() {
			return $this->morphToMany( Resource::class, 'resourcable' );
		}

	}
