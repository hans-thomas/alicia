<?php

	namespace Hans\Alicia\Tests\Feature\Actions;

	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Facades\Alicia;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Hans\Alicia\Tests\TestCase;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;

	class ExportActionTest extends TestCase {

		/**
		 * @test
		 *
		 * @return void
		 * @throws AliciaException
		 */
		public function exportDifferentResolution(): void {
			$content = Alicia::upload(
				UploadedFile::fake()
				            ->createWithContent(
					            'posty.jpg',
					            file_get_contents( __DIR__ . '/../../resources/posty.jpg' )
				            )
			)
			                 ->export()
			                 ->getData();

			$parent = Arr::get( $content, 'parents.0' );
			foreach ( $content[ $parent[ 'id' ] . '-children' ] as $data ) {
				$model = ResourceModel::query()->findOrFail( $data[ 'id' ] );
				$this->assertEquals( $parent[ 'directory' ], $model->directory );
				$this->assertEquals( $parent[ 'id' ], $model->parent_id );

				$this->assertDirectoryExists( alicia_storage()->path( $model->directory ) );
				$this->assertFileExists( alicia_storage()->path( $model->path ) );
			}

		}

	}