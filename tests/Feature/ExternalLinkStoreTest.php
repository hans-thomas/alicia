<?php


	namespace Hans\Alicia\Tests\Feature;

	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Tests\TestCase;


	class ExternalLinkStoreTest extends TestCase {

		/**
		 * @test
		 *
		 *
		 * @return void
		 */
		public function externalLinkStore() {
			$this->postJson( route( 'alicia.test.external', [ 'field' => 'link' ] ), [
				'link' => $link = 'http://laravel.com/img/homepage/vapor.jpg'
			] )
			     ->assertCreated()
			     ->assertJsonStructure( [
				     'id',
				     'path',
				     'file',
				     'extension',
				     'options'
			     ] )
			     ->content();


			$this->assertDatabaseHas(
				ResourceModel::class,
				[
					'title'     => 'vapor',
					'link'      => $link,
					'extension' => 'jpg',
					'external'  => true,
				]
			);
		}
	}
