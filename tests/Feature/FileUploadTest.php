<?php


	namespace Hans\Alicia\Tests\Feature;


	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;

	class FileUploadTest extends TestCase {


		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function uploadAZipedFile() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'uploadAZipedFile' ] ), [
				'uploadAZipedFile' => UploadedFile::fake()->create( 'ziped.file.zip', 10230, 'application/zip' )
			] );
			$response->assertCreated()->assertJsonStructure( [
				'id',
				'path',
				'file',
				'extension',
				'options'
			] );
			$data  = (object) json_decode( $response->content() );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertDatabaseHas( $model->getTable(), [
				'title'     => 'ziped-file',
				'path'      => $model->path,
				'external'  => false,
				'file'      => $model->file,
				'extension' => 'zip'
			] );
			$this->assertDirectoryExists( $this->storage->path( $model->path ) );
			$this->assertEquals( $model->path . '/' . $model->file, $model->address );
			$this->assertFileExists( $this->storage->path( $model->address ) );

			$this->assertTrue( $this->alicia->delete( $model->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function uploadAnImage() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'UploadAnImage' ] ), [
				'UploadAnImage' => UploadedFile::fake()->image( 'imagefile.png', 512, 512 )
			] );
			$response->assertCreated()->assertJsonStructure( [
				'id',
				'path',
				'file',
				'extension',
				'options'
			] );
			$data  = json_decode( $response->content() );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertDatabaseHas( $model->getTable(), [
				'title'     => 'imagefile',
				'path'      => $model->path,
				'external'  => false,
				'file'      => $model->file,
				'extension' => 'png'
			] );
			$this->assertDirectoryExists( $this->storage->path( $model->path ) );
			$this->assertEquals( $model->path . '/' . $model->file, $model->address );
			$this->assertFileExists( $this->storage->path( $model->address ) );

			$this->assertTrue( $this->alicia->delete( $model->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function uploadAVideo() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'UploadAVideo' ] ), [
				'UploadAVideo' => UploadedFile::fake()
				                              ->createWithContent( 'video.mp4',
					                              file_get_contents( __DIR__ . '/../resources/video.mp4' ) )
			] );
			$response->assertCreated()->assertJsonStructure( [
				'id',
				'path',
				'file',
				'extension',
				'options'
			] );
			$data  = json_decode( $response->content() );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertDatabaseHas( $model->getTable(), [
				'title'     => "video",
				'path'      => $model->path,
				'external'  => false,
				'file'      => $model->file,
				'extension' => 'mp4'
			] );
			$this->assertFileExists( $this->storage->path( $model->path . '/' . $model->hls ) );
			$this->assertDirectoryExists( $this->storage->path( $model->path ) );
			$this->assertEquals( $model->path . '/' . $model->file, $model->address );
			$this->assertFileExists( $this->storage->path( $model->address ) );

			$this->assertTrue( $this->alicia->delete( $model->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function dontGenerateHlsWhenHlsDisabled() {
			$this->app[ 'config' ]->set( 'alicia.hls.enable', false );
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'UploadAVideo' ] ), [
				'UploadAVideo' => UploadedFile::fake()
				                              ->createWithContent( 'does-not-have-hls-file.mp4',
					                              file_get_contents( __DIR__ . '/../resources/video.mp4' ) )
			] );
			$response->assertCreated()->assertJsonStructure( [
				'id',
				'path',
				'file',
				'extension',
				'options'
			] );
			$data  = json_decode( $response->content() );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertDatabaseHas( $model->getTable(), [
				'title'     => "does-not-have-hls-file",
				'path'      => $model->path,
				'external'  => false,
				'file'      => $model->file,
				'extension' => 'mp4'
			] );
			$this->assertNull( $model->hls );
			$this->assertDirectoryDoesNotExist( $model->path . '/hls/' );
			$this->assertFileExists( $this->storage->path( $model->address ) );

			$this->assertTrue( $this->alicia->delete( $model->id ) );
		}


		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function generatedFilesAndFoldersDeletedAfterModelDestroyed() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'UploadAVideo' ] ), [
				'UploadAVideo' => UploadedFile::fake()
				                              ->createWithContent( 'video.mp4',
					                              file_get_contents( __DIR__ . '/../resources/video.mp4' ) )
			] )->assertCreated();
			$data     = json_decode( $response->content() );
			$model    = ResourceModel::findOrFail( $data->id );

			$this->assertFileExists( $this->storage->path( $model->path . '/' . $model->hls ) );
			$this->assertDirectoryExists( $this->storage->path( $model->path ) );
			$this->assertEquals( $model->path . '/' . $model->file, $model->address );
			$this->assertFileExists( $this->storage->path( $model->address ) );

			$duplicated = clone $model;
			$this->assertTrue( $this->alicia->delete( $model->id ) );

			$this->assertFileDoesNotExist( $this->storage->path( $duplicated->path . '/' . $duplicated->hls ) );
			$this->assertDirectoryDoesNotExist( $this->storage->path( $duplicated->path ) );
			$this->assertFileDoesNotExist( $this->storage->path( $duplicated->address ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function exportDifferentResolution() {
			$response = $this->postJson( route( 'alicia.test.upload.export', [ 'field' => 'ExportAPhoto' ] ), [
				'ExportAPhoto' => UploadedFile::fake()
				                              ->createWithContent( 'posty.jpg',
					                              file_get_contents( __DIR__ . '/../resources/posty.jpg' ) )
			] )->assertCreated();
			$content  = (array) json_decode( $response->getContent() );

			$parent = Arr::get( $content, 'parents.0' );
			foreach ( $content[ $parent->id . '-children' ] as $data ) {
				$model = ResourceModel::query()->findOrFail( $data->id );
				$this->assertEquals( $parent->path, $model->path );
				$this->assertEquals( $parent->id, $model->parent_id );

				$this->assertDirectoryExists( $this->storage->path( $model->path ) );
				$this->assertFileExists( $this->storage->path( $model->address ) );
			}

		}


	}
