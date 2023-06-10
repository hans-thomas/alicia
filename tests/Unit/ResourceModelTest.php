<?php


	namespace Hans\Alicia\Tests\Unit;


	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Facades\Signature;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\URL;
	use Symfony\Component\HttpFoundation\BinaryFileResponse;


	class ResourceModelTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function urlAsExternal(): void {
			$model = Alicia::external( $link = 'https://laravel.com/img/homepage/vapor.jpg' )->getData();

			$this->assertStringEqualsStringIgnoringLineEndings( $link, $model->link );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function urlAsSigned(): void {
			$model = Alicia::upload(
				UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			)
			               ->getData();

			$this->assertEquals(
				substr(
					$link = URL::temporarySignedRoute(
						'alicia.download',
						now()->addMinutes( alicia_config( 'expiration', '30' ) ),
						[
							'resource' => $model->id,
							'hash'     => Signature::create()
						]
					),
					0,
					strpos( $link, '?' )
				),
				substr( $url = $model->url, 0, strpos( $url, '?' ) )
			);

		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function urlAsNotSigned(): void {
			config()->set( 'alicia.signed', false );

			$model = Alicia::upload(
				UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			)
			               ->getData();

			$this->assertEquals( route( 'alicia.download', [ 'resource' => $model ] ), $model->url );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function hlsUrl(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertEquals( url( 'resources/' . $model->path . '/' . $model->hls ), $model->hlsUrl );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function hlsUrlAsHlsDisabled(): void {
			config()->set( 'alicia.hls.enable', false );

			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertEquals( url( 'resources/' . $model->path . '/' . $model->file ), $model->hlsUrl );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function hlsUrlAsAudioFile(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'g-eazy-freestyle.mp3',
					            file_get_contents( __DIR__ . '/../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
				            )
			)
			               ->getData();

			$this->assertInstanceOf(
				BinaryFileResponse::class,
				$model->hlsUrl
			);
			self::assertStringEqualsStringIgnoringLineEndings(
				$model->fullAddress,
				$model->hlsUrl->getFile()->getPath() . '/' . $model->hlsUrl->getFile()->getFilename()
			);
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function address(): void {
			$model = Alicia::upload(
				UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			)
			               ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $model->path ) );
			$this->assertStringEqualsStringIgnoringLineEndings( $model->path . '/' . $model->file, $model->address );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function fullAddress() {
			$model = Alicia::upload(
				UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			)
			               ->getData();

			self::assertTrue( alicia_storage()->fileExists( $model->address ) );
			self::assertStringEqualsStringIgnoringLineEndings(
				alicia_storage()->path( $model->address ),
				$model->fullAddress
			);
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function getOptions(): void {
			$model = Alicia::upload(
				UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			)
			               ->getData();

			self::assertEquals(
				[
					"size"     => 3492,
					"mimeType" => "image/png",
					"width"    => 1080,
					"height"   => 1080,
				],
				$model->getOptions()
			);
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function updateOptions(): void {
			$model = Alicia::upload(
				UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			)
			               ->getData();

			self::assertEquals(
				[
					"size"     => 3492,
					"mimeType" => "image/png",
					"width"    => 1080,
					"height"   => 1080,
				],
				$model->getOptions()
			);

			$model->updateOptions( [ 'size' => 2943, 'new' => 'value' ] );

			self::assertEquals(
				[
					"size"     => 2943,
					"mimeType" => "image/png",
					"width"    => 1080,
					"height"   => 1080,
					'new'      => 'value',
				],
				$model->getOptions()
			);
		}

	}
