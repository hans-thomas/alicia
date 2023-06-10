<?php

	namespace Hans\Alicia\Tests\Feature\Actions;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;

	class FromFileActionTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function fromFile(): void {
			$file = __DIR__ . '/../../resources/posty.jpg';

			$model = Alicia::fromFile( $file )->getData();

			self::assertFileExists( $model->fullPath );
			self::assertFileEquals(
				$file,
				$model->fullPath
			);

		}
	}