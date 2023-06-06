<?php


	namespace Hans\Alicia\Tests\Feature;


	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class JobsTest extends TestCase {

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function ImageJobs() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'ImageJobs' ] ), [
				'ImageJobs' => UploadedFile::fake()
				                           ->createWithContent( 'posty.jpg',
					                           file_get_contents( __DIR__ . '/../resources/posty.jpg' ) )
			] );
			$response->assertJsonStructure( [
				'id',
				'path',
				'file',
				'extension',
				'options'
			] );
			$data = json_decode( $response->content() );

			$this->assertDatabaseHas( ResourceModel::class, [
				'id' => $data->id
			] );
			$this->assertGreaterThanOrEqual( $data->options->size, filesize( __DIR__ . '/../resources/posty.jpg' ) );

			$this->assertTrue( Alicia::delete( $data->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function VideoJobs() {
			$this->withoutExceptionHandling();
			$response = $this->postJson(
				route( 'alicia.test.upload', [ 'field' => 'VideoJobs' ] ),
				[
					'VideoJobs' => UploadedFile::fake()
					                           ->createWithContent(
						                           'video.mp4',
						                           file_get_contents( __DIR__ . '/../resources/video.mp4' )
					                           )
				]
			)
			                 ->assertJsonStructure( [
				                 'id',
				                 'path',
				                 'file',
				                 'extension',
				                 'options'
			                 ] );

			$data = json_decode( $response->content() );
			$this->assertDatabaseHas( ResourceModel::class, [
				'id' => $data->id
			] );

			$this->assertFileExists( alicia_storage()->path( $data->path . '/' . $data->hls ) );

			$this->assertTrue( Alicia::delete( $data->id ) );
		}
	}
