<?php

	namespace Hans\Alicia\Services\Actions;

	use Hans\Alicia\Contracts\Actions;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Collection;

	class BatchUpload extends Actions {

		public function __construct(
			protected readonly array $files
		) {
		}

		/**
		 * Contain action's logic
		 *
		 * @return ResourceModel|Collection
		 * @throws AliciaException
		 */
		public function run(): Resource|Collection {
			$data = collect();
			foreach ( $this->files as $file ) {
				if ( $file instanceof UploadedFile ) {
					$data->push( ( new Upload( $file ) )->run() );
				} elseif ( is_string( $file ) ) {
					$data->push( ( new External( $file ) )->run() );
				}
			}

			return $data;
		}
	}