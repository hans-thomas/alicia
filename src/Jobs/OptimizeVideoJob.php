<?php


	namespace Hans\Alicia\Jobs;

	use FFMpeg\Format\Video\X264;
	use Hans\Alicia\Contracts\AliciaContract;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Facades\Storage;

	class OptimizeVideoJob implements ShouldQueue {
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		public ResourceModel $model;

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
		public function handle( AliciaContract $alicia ) {
			$storage = Storage::disk( 'resources' );
			// optimization
			$oldFile = $this->model->address;
			$this->model->ffmpeg()
			            ->export()
			            ->inFormat( new X264 )
			            ->save( $this->model->path . '/' . $newFile = $alicia->generateName() . '.' . $this->model->extension );
			// update file
			$this->model->update( [
				'file' => $newFile
			] );
			// update file's size
			$this->model->setOptions( [ 'size' => $storage->size( $this->model->address ) ] );
			// delete old file
			$alicia->deleteFile( $oldFile );
		}
	}
