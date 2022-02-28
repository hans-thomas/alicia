<?php


	namespace Hans\Alicia\Jobs;

	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Facades\Storage;

	class ClassificationJob implements ShouldQueue {
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		private ResourceModel $model;
		private array $configuration;
		private Filesystem $storage;

		/**
		 * Create a new job instance.
		 *
		 * @return void
		 */
		public function __construct( ResourceModel $model ) {
			$this->model         = $model;
			$this->configuration = config( 'alicia' );
			$this->storage       = Storage::disk( 'resources' );
		}

		/**
		 * Execute the job.
		 *
		 * @return void
		 * @throws \Exception
		 */
		public function handle() {
			if ( $this->model->isExternal() ) {
				return;
			}
			if ( $this->getConfig( 'temp' ) ) {
				$this->moveFileAndUpdateModel( $this->makeDirectoryIfNotExists() );
			}
		}

		private function moveFileAndUpdateModel( string $folder ): bool {
			if ( $this->storage->move( $this->model->address, $folder . '/' . $this->model->file ) ) {
				return $this->model->update( [
					'path' => $folder
				] );
			}

			return false;
		}

		private function makeDirectoryIfNotExists(): string {
			if ( ! $this->storage->exists( $folder = $this->getConfig( 'classification' ) ) ) {
				$this->storage->makeDirectory( $folder );
			}

			return $folder;
		}

		private function getConfig( string $key ) {
			return Arr::get( $this->configuration, $key );
		}
	}
