<?php


	namespace Hans\Alicia\Jobs;


	use FFMpeg\Format\Video\X264;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Bus\Queueable;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Foundation\Bus\Dispatchable;
	use Illuminate\Queue\InteractsWithQueue;
	use Illuminate\Queue\SerializesModels;
	use Illuminate\Support\Arr;

	class GenerateHLSJob implements ShouldQueue {
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
		public function handle() {
			if ( alicia_config( 'enable' ) ) {
				$export = $this->model->ffmpeg()->exportForHLS();

				foreach ( Arr::wrap( alicia_config( 'bitrate' ) ) as $bitrate ) {
					$export->addFormat( ( new X264 )->setKiloBitrate( $bitrate ) );
				}

				$export->setSegmentLength( alicia_config( 'setSegmentLength' ) ) // optional
				       ->setKeyFrameInterval( alicia_config( 'setKeyFrameInterval' ) ) // optional
				       ->save( $this->model->path . ( $hls = '/hls/' . generate_file_name() . '.m3u8' ) );

				$this->model->update( [ 'hls' => ltrim( $hls, '/' ) ] );
			}

		}

	}
