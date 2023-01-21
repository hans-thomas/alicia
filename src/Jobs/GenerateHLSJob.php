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
			$this->model         = $model;
		}

		/**
		 * Execute the job.
		 *
		 * @return void
		 */
		public function handle( AliciaContract $alicia ) {
			$data[ 'published_at' ] = now();
			if ( $this->getConfig( 'enable' ) ) {
				$export = $this->model->ffmpeg()->exportForHLS();
				foreach ( Arr::wrap( $this->getConfig( 'bitrate' ) ) as $bitrate ) {
					$export->addFormat( ( new X264 )->setKiloBitrate( $bitrate ) );
				}
				$export->setSegmentLength( $this->getConfig( 'setSegmentLength' ) ) // optional
				       ->setKeyFrameInterval( $this->getConfig( 'setKeyFrameInterval' ) ) // optional
				       ->save( $this->model->path . ( $hls = '/hls/' . $alicia->generateName() . '.m3u8' ) );
				$data[ 'hls' ] = ltrim( $hls, '/' );
			}


			$this->model->update( $data );
		}

		private function getConfig( string $key ) {
			return Arr::get( config( 'alicia.hls' ), $key );
		}
	}
