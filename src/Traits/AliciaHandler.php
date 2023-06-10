<?php


	namespace Hans\Alicia\Traits;


	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Models\Resource;
	use Illuminate\Database\Eloquent\Relations\MorphToMany;

	trait AliciaHandler {

		public function deleteAttachments(): array {
			$ids = $this->attachments()->select( [ 'id', 'directory', 'external' ] )->pluck( 'id' )->toArray();
			$this->attachments()->detach( $ids );

			return Alicia::batchDelete( $ids );
		}

		public function attachments(): MorphToMany {
			return $this->morphToMany( Resource::class, 'resourcable' )
			            ->orderByPivot( 'attached_at' )
			            ->withPivot( 'key', 'attached_at' );
		}

		public function attachTo( Resource $resource, string $key = null ): array {
			$data = $key ?
				[ $resource->id => [ 'key' => $key ] ] :
				[ $resource->id ];

			return $this->attachments()->syncWithoutDetaching( $data );
		}

		public function attachManyTo( array $ids ): array {
			return $this->attachments()->syncWithoutDetaching( $ids );
		}

		public function attachment(): ?Resource {
			return $this->attachments()->limit( 1 )->first();
		}


	}
