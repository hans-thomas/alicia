<?php

	namespace Hans\Alicia\Tests\Feature\Actions;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class BatchUploadActionTest extends TestCase {

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function batchUpload() {
			$data = Alicia::batch(
				[
					UploadedFile::fake()
					            ->createWithContent(
						            'video.mp4',
						            file_get_contents( __DIR__ . '/../resources/video.mp4' )
					            ),
					UploadedFile::fake()->image( 'eminem.png', 512, 512 ),
					UploadedFile::fake()->create( 'ziped-file.zip', 10230, 'application/zip' ),
					UploadedFile::fake()
					            ->createWithContent(
						            'g-eazy-freestyle.mp3',
						            file_get_contents( __DIR__ . '/../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
					            ),
					$url = 'http://laravel.com/img/homepage/vapor.jpg',
				]
			)
			              ->getData();


			$video = $data[ 0 ];

			$this->assertDirectoryExists( alicia_storage()->path( $video->path . '/hls' ) );
			$this->assertFileExists( alicia_storage()->path( $video->path . '/' . $video->hls ) );

			$this->assertDirectoryExists( alicia_storage()->path( $video->path ) );
			$this->assertFileExists( alicia_storage()->path( $video->address ) );

			$image = $data[ 1 ];

			$this->assertDirectoryExists( alicia_storage()->path( $image->path ) );
			$this->assertFileExists( alicia_storage()->path( $image->address ) );

			$file = $data[ 2 ];

			$this->assertDirectoryExists( alicia_storage()->path( $file->path ) );
			$this->assertFileExists( alicia_storage()->path( $file->address ) );

			$audio = $data[ 3 ];

			$this->assertDirectoryExists( alicia_storage()->path( $audio->path ) );
			$this->assertFileExists( alicia_storage()->path( $audio->address ) );

			$link = $data[ 4 ];

			$this->assertStringEqualsStringIgnoringLineEndings(
				$url,
				$link->link
			);

		}

	}
