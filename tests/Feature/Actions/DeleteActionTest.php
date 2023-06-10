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
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			               ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $model->path . '/hls' ) );
			$this->assertFileExists( alicia_storage()->path( $model->path . '/' . $model->hls ) );

			$this->assertDirectoryExists( alicia_storage()->path( $model->path ) );
			$this->assertFileExists( alicia_storage()->path( $model->address ) );

			$this->assertTrue( Alicia::delete( $model->id ) );

			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $model->path . '/hls' ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $model->path . '/' . $model->hls ) );

			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $model->path ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $model->address ) );
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
					            file_get_contents( __DIR__ . '/../resources/video.mp4' )
				            )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelA->path . '/hls' ) );
			$this->assertFileExists( alicia_storage()->path( $modelA->path . '/' . $modelA->hls ) );

			$this->assertDirectoryExists( alicia_storage()->path( $modelA->path ) );
			$this->assertFileExists( alicia_storage()->path( $modelA->address ) );

			$modelB = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'g-eazy-freestyle.mp3',
					            file_get_contents( __DIR__ . '/../resources/G-Eazy-Break_From_LA_Freestyle.mp3' )
				            )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelB->path ) );
			$this->assertFileExists( alicia_storage()->path( $modelB->address ) );

			$modelC = Alicia::upload(
				UploadedFile::fake()->create( 'post-malone-chemical.zip', 10230, 'application/zip' )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelC->path ) );
			$this->assertFileExists( alicia_storage()->path( $modelC->address ) );

			$modelD = Alicia::upload(
				UploadedFile::fake()->image( 'eminem.png', 512, 512 )
			)
			                ->getData();

			$this->assertDirectoryExists( alicia_storage()->path( $modelD->path ) );
			$this->assertFileExists( alicia_storage()->path( $modelD->address ) );

			$this->assertIsArray(
				$result = Alicia::batchDelete( [
					$modelA->id,
					$modelB->id,
					$modelC->id,
					$modelD->id
				] )
			);

			self::assertTrue( collect( $result )->every( fn( $item ) => $item === true ) );

			// modelA
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelA->path . '/hls' ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelA->path . '/' . $modelA->hls ) );

			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelA->path ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelA->address ) );

			// modelB
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelB->path ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelB->address ) );
			// modelC
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelC->path ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelC->address ) );
			// modelD
			$this->assertDirectoryDoesNotExist( alicia_storage()->path( $modelD->path ) );
			$this->assertFileDoesNotExist( alicia_storage()->path( $modelD->address ) );

		}


	}