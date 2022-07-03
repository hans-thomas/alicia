<?php


	namespace Hans\Alicia\Tests\Unit;


	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Facades\URL;


	class ResourceModelTest extends TestCase {

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function generateSignedUrl() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'file' ] ), [
				'file' => UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			] )->assertCreated();
			$data     = json_decode( $response->content() );
			$this->assertDatabaseHas( ResourceModel::class, [ 'id' => $data->id ] );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertEquals( substr( $link = URL::temporarySignedRoute( 'alicia.download',
				now()->addMinutes( $this->getConfig( 'expiration', '30' ) ), [
					'resource' => $model->id,
					'hash'     => $this->signature->create()
				] ), 0, strpos( $link, '?' ) ), substr( $link = $model->url, 0, strpos( $link, '?' ) ) );

			$this->assertTrue( $this->alicia->delete( $data->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function generateNotSignedUrl() {
			$this->app[ 'config' ]->set( 'alicia.signed', false );
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'file' ] ), [
				'file' => UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			] )->assertCreated();
			$data     = json_decode( $response->content() );
			$this->assertDatabaseHas( ResourceModel::class, [ 'id' => $data->id ] );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertEquals( route( 'alicia.download', [ 'resource' => $model ] ), $model->url );

			$this->assertTrue( $this->alicia->delete( $data->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function generateHlsUrl() {
			$this->withoutExceptionHandling();
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'generateHlsUrl' ] ), [
				'generateHlsUrl' => UploadedFile::fake()
				                                ->createWithContent( 'video.mp4',
					                                file_get_contents( __DIR__ . '/../resources/video.mp4' ) )
			] );
			$response->assertCreated();
			$data = json_decode( $response->content() );
			$this->assertDatabaseHas( ResourceModel::class, [ 'id' => $data->id ] );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertEquals( url( 'resources/' . $model->path . '/' . $model->hls ), $model->hlsUrl );

			$this->assertTrue( $this->alicia->delete( $model->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function getDirectLinkIfHlsIsDisabled() {
			// $this->withoutExceptionHandling();
			$this->app[ 'config' ]->set( 'alicia.hls.enable', false );
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'generateHlsUrl' ] ), [
				'generateHlsUrl' => UploadedFile::fake()
				                                ->createWithContent( 'video.mp4',
					                                file_get_contents( __DIR__ . '/../resources/video.mp4' ) )
			] );
			$response->assertCreated();
			$data = json_decode( $response->content() );
			$this->assertDatabaseHas( ResourceModel::class, [ 'id' => $data->id ] );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertEquals( url( 'resources/' . $model->path . '/' . $model->file ), $model->hlsUrl );

			$this->assertTrue( $this->alicia->delete( $model->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function getExternalLink() {
			$response = $this->postJson( route( 'alicia.test.external', [ 'field' => 'external' ] ), [
				'external' => $link = 'https://laravel.com/img/homepage/vapor.jpg'
			] );
			$response->assertCreated();
			$data = json_decode( $response->content() );
			$this->assertDatabaseHas( ResourceModel::class, [ 'id' => $data->id ] );
			$model = ResourceModel::findOrFail( $data->id );
			$this->assertTrue( $model->isExternal() );
			$this->assertEquals( $link, $model->url );

			$this->assertTrue( $this->alicia->delete( $model->id ) );
		}

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function getStoragePath() {
			$response = $this->postJson( route( 'alicia.test.upload', [ 'field' => 'file' ] ), [
				'file' => UploadedFile::fake()->image( 'g-eazy.png', 1080, 1080 )
			] )->assertCreated();
			$data     = json_decode( $response->content() );
			$model    = ResourceModel::findOrFail( $data->id );
			$this->assertTrue( $this->storage->fileExists( $model->address ) );
		}
	}
