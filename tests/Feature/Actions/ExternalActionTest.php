<?php


	namespace Hans\Alicia\Tests\Feature\Actions;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;


	class ExternalActionTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function external(): void {
			$model = Alicia::external(
				$link = 'http://laravel.com/img/homepage/vapor.jpg'
			)
			               ->getData();

			$this->assertModelExists( $model );
			$this->assertDatabaseHas(
				$model->getTable(),
				[
					'title'     => 'vapor',
					'link'      => $link,
					'extension' => 'jpg',
					'external'  => true,
				]
			);
		}

		/**
		 * @test
		 *
		 * @return void
		 */
		public function externalWithQueryString(): void {
			$model = Alicia::external(
				$link = 'http://laravel.com/img/homepage/vapor.jpg?query-string=true'
			)
			               ->getData();

			$this->assertModelExists( $model );
			$this->assertDatabaseHas(
				$model->getTable(),
				[
					'title'     => 'vapor',
					'link'      => $link,
					'extension' => 'jpg',
					'external'  => true,
				]
			);
		}
	}
