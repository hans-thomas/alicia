<?php


	namespace Hans\Alicia\Tests\Feature\Actions;


	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class UploadActionTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function uploadAZipedFile(): void {
			$model = Alicia::upload(
				UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			)
			               ->getData();

			$this->assertStringEqualsStringIgnoringLineEndings( 'zip', $model->extension );
			$this->assertDirectoryExists( alicia_storage()->path( $model->path ) );
			$this->assertFileExists( alicia_storage()->path( $model->address ) );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function uploadAnImage(): void {
			$model = Alicia::upload(
				UploadedFile::fake()->image( 'imagefile.png', 512, 512 )
			)
			               ->getData();

			$this->assertStringEqualsStringIgnoringLineEndings( 'png', $model->extension );
			$this->assertDirectoryExists( alicia_storage()->path( $model->path ) );
			$this->assertFileExists( alicia_storage()->path( $model->address ) );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function uploadAVideo(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();


			$this->assertStringEqualsStringIgnoringLineEndings( 'mp4', $model->extension );
			$this->assertDirectoryExists( alicia_storage()->path( $model->path ) );
			$this->assertFileExists( alicia_storage()->path( $model->address ) );
			$this->assertFileExists( alicia_storage()->path( $model->path . '/' . $model->hls ) );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function uploadAnAudio(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'g-eazy-freestyle.mp3',
					            file_get_contents( __DIR__ . '/../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
				            )
			)
			               ->getData();

			$this->assertStringEqualsStringIgnoringLineEndings( 'mp3', $model->extension );
			$this->assertDirectoryExists( alicia_storage()->path( $model->path ) );
			$this->assertFileExists( alicia_storage()->path( $model->address ) );
		}

	}
