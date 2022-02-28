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
	use Illuminate\Support\Arr;
	use Illuminate\Support\Facades\Storage;
	use function app;

	class GenerateHLSJob implements ShouldQueue {
		use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

		private ResourceModel $model;
		private Filesystem $storage;
		private AliciaContract $alicia;
		private array $configuration;

		/**
		 * Create a new job instance.
		 *
		 * @return void
		 */
		public function __construct( ResourceModel $model ) {
			$this->model         = $model;
			$this->storage       = Storage::disk( 'resources' );
			$this->configuration = config( 'alicia.hls' );
			$this->alicia        = app( AliciaContract::class );
		}

		/**
		 * Execute the job.
		 *
		 * @return void
		 */
		public function handle() {
			$data[ 'published_at' ] = now();
			if ( $this->getConfig( 'enable' ) ) {
				$export = $this->model->ffmpeg()->exportForHLS();
				foreach ( Arr::wrap( $this->getConfig( 'bitrate' ) ) as $bitrate ) {
					$export->addFormat( ( new X264 )->setKiloBitrate( $bitrate ) );
				}
				$export->setSegmentLength( $this->getConfig( 'setSegmentLength' ) ) // optional
				       ->setKeyFrameInterval( $this->getConfig( 'setKeyFrameInterval' ) ) // optional
				       ->save( $this->model->path . ( $hls = '/hls/' . $this->alicia->generateName() . '.m3u8' ) );
				$data[ 'hls' ] = ltrim( $hls, '/' );
			}


			$this->model->update( $data );
		}

		private function getConfig( string $key ) {
			return Arr::get( $this->configuration, $key );
		}
	}
