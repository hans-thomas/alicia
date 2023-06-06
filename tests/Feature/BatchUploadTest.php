<?php

	namespace Hans\Alicia\Tests\Feature;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class BatchUploadTest extends TestCase {

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function batchUpload() {
			//$this->withoutExceptionHandling();
			$response = $this->postJson(
				route( 'alicia.test.batch', [ 'field' => 'batchUpload' ] ),
				[
					'batchUpload' => [
						//UploadedFile::fake()->create( 'video.mp4', 10230,'video/mp4' ),
						UploadedFile::fake()->image( 'imagefile.png', 512, 512 ),
						UploadedFile::fake()->create( 'ziped.zip', 10230, 'application/zip' ),
						'http://laravel.com/img/homepage/vapor.jpg',
					],
				]
			)
			                 ->assertCreated()
			                 ->assertJsonStructure( [
				                 [
					                 'id',
					                 'path',
					                 'file',
					                 'extension',
					                 'options'
				                 ]
			                 ] );
			$data     = json_decode( $response->content() );
			collect( $data )->each( fn( $item ) => match ( $item->external ) {
				true => $this->checkExternals( $item ),
				false => $this->checkFiles( $item )
			} );

		}

		private function checkExternals( object $item ): void {
			$model = ResourceModel::findOrFail( $item->id );
			$this->assertDatabaseHas( ResourceModel::class, [
				'title'     => $item->title,
				'path'      => $item->path,
				'external'  => true,
				'file'      => $item->file,
				'extension' => $item->extension
			] );
			$this->assertTrue( Alicia::delete( $model->id ) );
		}

		private function checkFiles( object $item ): void {
			$model = ResourceModel::findOrFail( $item->id );
			$this->assertDatabaseHas( ResourceModel::class, [
				'title'     => $item->title,
				'path'      => $item->path,
				'external'  => false,
				'file'      => $item->file,
				'extension' => $item->extension
			] );
			$this->assertDirectoryExists( alicia_storage()->path( $item->path ) );
			$this->assertEquals( $item->path . '/' . $item->file, $model->address );
			$this->assertFileExists( alicia_storage()->path( $model->address ) );

			$this->assertTrue( Alicia::delete( $model->id ) );
		}
	}
