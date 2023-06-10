<?php

	namespace Hans\Alicia\Tests\Feature;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class ResourceControllerTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function download(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'g-eazy-freestyle.mp3',
					            file_get_contents( __DIR__ . '/../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
				            )
			)
			               ->getData();


			$this->getJson(
				uri: $model->downloadUrl
			)
			     ->assertOk()
			     ->assertDownload( "{$model->title}.{$model->extension}" );

		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function downloadExternal(): void {
			$model = Alicia::external(
				'http://laravel.com/img/homepage/vapor.jpg'
			)
			               ->getData();

			$this->getJson(
				uri: $model->downloadUrl
			)
			     ->assertOk()
			     ->assertDownload( "{$model->title}.{$model->extension}" );

			self::assertCount( 1, alicia_storage()->files( 'temp' ) );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function requestHasNotValidSignature(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'g-eazy-freestyle.mp3',
					            file_get_contents( __DIR__ . '/../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
				            )
			)
			               ->getData();

			$this->getJson(
				uri: $model->downloadUrl . 'sdfsdf4534s'
			)
			     ->assertBadRequest();
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function requestHasNotValidHash(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'g-eazy-freestyle.mp3',
					            file_get_contents( __DIR__ . '/../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
				            )
			)
			               ->getData();

			$this->getJson(
				uri: $model->downloadUrl,
				headers: [
					'User-Agent' => fake()->userAgent()
				]
			)
			     ->assertUnauthorized();
		}


	}
