<?php


	namespace Hans\Alicia\Jobs;

	use FFMpeg\Format\Video\X264;
	use Hans\Alicia\Contracts\AliciaContract;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\Storage;

	class OptimizeVideoJob implements ShouldQueue {
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		private ResourceModel $model;
		private Filesystem $storage;
		private AliciaContract $alicia;

		/**
		 * Create a new job instance.
		 *
		 * @return void
		 */
		public function __construct( ResourceModel $model ) {
			$this->model   = $model;
			$this->storage = Storage::disk( 'resources' );
			$this->alicia  = app( AliciaContract::class );
		}

		/**
		 * Execute the job.
		 *
		 * @return void
		 */
		public function handle() {
			if ( config( 'alicia.optimization.videos' ) ) {
				// optimization
				$oldFile = $this->model->address;
				$this->model->ffmpeg()
				            ->export()
				            ->inFormat( new X264 )
				            ->save( $this->model->path . '/' . $newFile = $this->alicia->generateName() . '.' . $this->model->extension );
				// update file
				$this->model->update( [
					'file' => $newFile
				] );
				// update file's size
				$this->model->setOptions( [ 'size' => $this->storage->size( $this->model->address ) ] );
				// delete old file
				$this->alicia->deleteFile( $oldFile );
			}
		}
	}
