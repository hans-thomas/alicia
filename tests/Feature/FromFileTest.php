<?php

	namespace Hans\Alicia\Tests\Feature;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;

	class FromFileTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function fromFile(): void {
			$file = __DIR__ . '/../resources/posty.jpg';

			$model = Alicia::fromFile( $file )->getData();

			self::assertFileExists( $model->fullAddress );
			self::assertFileEquals(
				$file,
				$model->fullAddress
			);

		}
	}