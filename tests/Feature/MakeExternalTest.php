<?php

	namespace Hans\Alicia\Tests\Feature;

	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;

	class MakeExternalTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 */
		public function makeExternal(): void {
			$model = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'posty.jpg',
					            file_get_contents( __DIR__ . '/../resources/posty.jpg' )
				            )
			)
			               ->getData();

			self::assertTrue( $model->isNotExternal() );
			self::assertFileExists( $fullAddress = $model->fullAddress );
			self::assertNull( $model->link );

			$link = 'http://laravel.com/img/homepage/vapor.jpg';

			Alicia::makeExternal( $model, $link )->getData();

			self::assertTrue( $model->isExternal() );
			self::assertFileDoesNotExist( $fullAddress );
			self::assertStringEqualsStringIgnoringLineEndings(
				$link,
				$model->link
			);
			self::assertNull( $model->path );
			self::assertNull( $model->file );
		}

	}