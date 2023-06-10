<?php


	namespace Hans\Alicia\Jobs;

	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Spatie\LaravelImageOptimizer\OptimizerChainFactory;

	class OptimizePictureJob implements ShouldQueue {
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		/**
		 * Create a new job instance.
		 *
		 * @return void
		 */
		public function __construct(
			protected ResourceModel $model
		) {

		}

		/**
		 * Execute the job.
		 *
		 * @return void
		 */
		public function handle() {
			$settings = require __DIR__ . '/../../config/image-optimizer.php';
			OptimizerChainFactory::create( $settings )->optimize( alicia_storage()->path( $this->model->path ) );
			$this->model->updateOptions( [ 'size' => alicia_storage()->size( $this->model->path ) ] );
		}
	}
