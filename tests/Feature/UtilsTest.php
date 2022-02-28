<?php

	namespace Hans\Alicia\Tests\Feature;

	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class UtilsTest extends TestCase {
		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function deleteFile() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'uploadAZipedFile' ] ), [
				'uploadAZipedFile' => UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			] );

			$response->assertCreated();
			$data  = json_decode( $response->getContent() );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertTrue( $this->storage->exists( $model->address ) );
			$this->alicia->deleteFile( $model->address );
			$this->assertFalse( $this->storage->exists( $model->address ) );
			$this->alicia->delete( $model->id );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function deleteAModel() {
			$response       = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'uploadAZipedFile' ] ), [
				'uploadAZipedFile' => UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			] );
			$secondResponse = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'uploadAZipedFile' ] ), [
				'uploadAZipedFile' => UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			] );

			// first request
			$data = json_decode( $response->getContent() );
			$this->assertDatabaseHas( ResourceModel::class, $attributes = [
				'id'   => $data->id,
				'path' => $data->path,
				'file' => $data->file,
			] );
			$this->assertFileExists( $this->storage->path( $address = $data->path . '/' . $data->file ) );
			$this->alicia->delete( $data->id );
			$this->assertDatabaseMissing( ResourceModel::class, $attributes );
			$this->assertFileDoesNotExist( $this->storage->path( $address ) );

			// second request
			$data = json_decode( $secondResponse->getContent() );
			$this->assertDatabaseHas( ResourceModel::class, $attributes = [
				'id'   => $data->id,
				'path' => $data->path,
				'file' => $data->file,
			] );
			$this->assertFileExists( $this->storage->path( $address = $data->path . '/' . $data->file ) );
			$this->alicia->delete( $data->id );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function batchDeleteModels() {
			$response       = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'uploadAZipedFile' ] ), [
				'uploadAZipedFile' => UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			] );
			$secondResponse = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'uploadAZipedFile' ] ), [
				'uploadAZipedFile' => UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			] );

			$firstModel  = json_decode( $response->getContent() );
			$secondModel = json_decode( $secondResponse->getContent() );


			$this->alicia->batchDelete( [ $firstModel->id, $secondModel->id ] );
			// first model assertion
			$this->assertDatabaseMissing( ResourceModel::class, [
				'id'   => $firstModel->id,
				'path' => $firstModel->path,
				'file' => $firstModel->file,
			] );
			$this->assertFileDoesNotExist( $this->storage->path( $firstModel->path . '/' . $firstModel->file ) );
			// second model assertion
			$this->assertDatabaseMissing( ResourceModel::class, [
				'id'   => $secondModel->id,
				'path' => $secondModel->path,
				'file' => $secondModel->file,
			] );
			$this->assertFileDoesNotExist( $this->storage->path( $secondModel->path . '/' . $secondModel->file ) );

		}
	}
