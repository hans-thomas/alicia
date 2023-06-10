<?php


	namespace Hans\Alicia\Tests\Feature;


	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Jobs\GenerateHLSJob;
	use Hans\Alicia\Jobs\OptimizePictureJob;
	use Hans\Alicia\Jobs\OptimizeVideoJob;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\Bus;

	class JobsTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function ImageJobs(): void {
			Bus::fake();

			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'posty.jpg',
					            file_get_contents( __DIR__ . '/../resources/posty.jpg' )
				            )
			)
			               ->getData();

			$this->assertGreaterThanOrEqual(
				$model->options[ 'size' ],
				filesize( __DIR__ . '/../resources/posty.jpg' )
			);

			Bus::assertDispatched( OptimizePictureJob::class );

			Bus::assertNotDispatched( OptimizeVideoJob::class );
			Bus::assertNotDispatched( GenerateHLSJob::class );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function VideoJobs(): void {
			Bus::fake();

			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertFileExists( alicia_storage()->path( $model->directory . '/' . $model->hls ) );

			Bus::assertChained( [
				OptimizeVideoJob::class,
				GenerateHLSJob::class
			] );

			Bus::assertNotDispatched( OptimizePictureJob::class );

		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function VideoJobsWithHlsDisabled(): void {
			config()->set( 'alicia.hls.enable', false );

			Bus::fake();

			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertNull( $model->hls );

			Bus::assertDispatched( OptimizeVideoJob::class );

			Bus::assertNotDispatched( GenerateHLSJob::class );
			Bus::assertNotDispatched( OptimizePictureJob::class );

		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function VideoJobsWithOptimizationDisabled(): void {
			config()->set( 'alicia.optimization.videos', false );

			Bus::fake();

			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertFileExists( alicia_storage()->path( $model->directory . '/' . $model->hls ) );

			Bus::assertDispatched( GenerateHLSJob::class );

			Bus::assertNotDispatched( OptimizeVideoJob::class );
			Bus::assertNotDispatched( OptimizePictureJob::class );

		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function VideoJobsWithOptimizationAndHlsDisabled(): void {
			config()->set( 'alicia.hls.enable', false );
			config()->set( 'alicia.optimization.videos', false );

			Bus::fake();

			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertFileExists( alicia_storage()->path( $model->directory . '/' . $model->hls ) );

			Bus::assertNotDispatched( OptimizeVideoJob::class );
			Bus::assertNotDispatched( GenerateHLSJob::class );
			Bus::assertNotDispatched( OptimizePictureJob::class );

		}


	}
