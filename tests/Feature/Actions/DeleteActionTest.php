<?php

	namespace Hans\Alicia\Tests\Feature\Actions;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class DeleteActionTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function deleteResource(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $model->directory . '/hls' ) );
			$this->assertFileExists( alicia_storage()->path( $model->directory . '/' . $model->hls ) );

			$this->assertDirectoryExists( alicia_storage()->path( $model->directory ) );
			$this->assertFileExists( alicia_storage()->path( $model->path ) );

			$this->assertTrue( Alicia::delete( $model->id ) );

			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $model->directory . '/hls' ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $model->directory . '/' . $model->hls ) );

			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $model->directory ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $model->path ) );
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function batchDelete(): void {
			$modelA = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'video.mp4',
					            file_get_contents( __DIR__ . '/../../resources/video.mp4' )
				            )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelA->directory . '/hls' ) );
			$this->assertFileExists( alicia_storage()->path( $modelA->directory . '/' . $modelA->hls ) );

			$this->assertDirectoryExists( alicia_storage()->path( $modelA->directory ) );
			$this->assertFileExists( alicia_storage()->path( $modelA->path ) );

			$modelB = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'g-eazy-freestyle.mp3',
					            file_get_contents( __DIR__ . '/../../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
				            )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelB->directory ) );
			$this->assertFileExists( alicia_storage()->path( $modelB->path ) );

			$modelC = Alicia::upload(
				UploadedFile::fake()->create( 'post-malone-chemical.zip', 10230, 'application/zip' )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelC->directory ) );
			$this->assertFileExists( alicia_storage()->path( $modelC->path ) );

			$modelD = Alicia::upload(
				UploadedFile::fake()->image( 'eminem.png', 512, 512 )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelD->directory ) );
			$this->assertFileExists( alicia_storage()->path( $modelD->path ) );

			$this->assertIsArray(
				$result = Alicia::batchDelete( [
					$modelA,
					$modelB->id,
					$modelC->id,
					$modelD->id
				] )
			);

			self::assertTrue( collect( $result )->every( fn( $item ) => $item === true ) );

			// modelA
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelA->directory . '/hls' ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelA->directory . '/' . $modelA->hls ) );

			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelA->directory ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelA->path ) );

			// modelB
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelB->directory ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelB->path ) );
			// modelC
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelC->directory ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelC->path ) );
			// modelD
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelD->directory ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelD->path ) );

		}


	}