<?php


	namespace Hans\Alicia\Jobs;

	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\Storage;
	use Spatie\LaravelImageOptimizer\OptimizerChainFactory;

	class OptimizePictureJob implements ShouldQueue {
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		private ResourceModel $model;

		/**
		 * Create a new job instance.
		 *
		 * @return void
		 */
		public function __construct( ResourceModel $model ) {
			$this->model = $model;
		}

		/**
		 * Execute the job.
		 *
		 * @return void
		 */
		public function handle() {
			$storage  = Storage::disk( 'resources' );
			$settings = require __DIR__ . '/../../config/image-optimizer.php';
			OptimizerChainFactory::create( $settings )->optimize( $storage->path( $this->model->address ) );
			$this->model->setOptions( [ 'size' => $storage->size( $this->model->address ) ] );
		}
	}
