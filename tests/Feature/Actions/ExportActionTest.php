<?php

namespace Hans\Alicia\Tests\Feature\Actions;

use Hans\Alicia\Exceptions\AliciaException;
use Hans\Alicia\Facades\Alicia;
use Hans\Alicia\Models\Resource as ResourceModel;
use Hans\Alicia\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Spatie\Image\Exceptions\InvalidManipulation;

class ExportActionTest extends TestCase
{
    /**
     * @test
     *
     * @throws AliciaException|InvalidManipulation
     *
     * @return void
     */
    public function export(): void
    {
        $content = Alicia::upload(
            UploadedFile::fake()
                        ->createWithContent(
                            'posty.jpg',
                            file_get_contents(__DIR__.'/../../resources/posty.jpg')
                        )
        )
                         ->export()
                         ->getData();

        $parent = Arr::get($content, 'parents.0');
        $resolutions = [
            [960, 540],
            [1280, 720],
            [1920, 1080],
        ];
        foreach ($content[$parent['id'].'-children'] as $key => $data) {
            $model = ResourceModel::query()->findOrFail($data['id']);
            $this->assertEquals($parent['directory'], $model->directory);
            $this->assertEquals($parent['id'], $model->parent_id);

            self::assertEquals($resolutions[$key][0], $model->options['height']);
            self::assertEquals($resolutions[$key][1], $model->options['width']);

            $this->assertDirectoryExists(alicia_storage()->path($model->directory));
            $this->assertFileExists(alicia_storage()->path($model->path));
        }
    }

    /**
     * @test
     *
     * @throws AliciaException|InvalidManipulation
     *
     * @return void
     */
    public function exportWithCustomResolution(): void
    {
        $content = Alicia::upload(
            UploadedFile::fake()
                        ->createWithContent(
                            'posty.jpg',
                            file_get_contents(__DIR__.'/../../resources/posty.jpg')
                        )
        )
                         ->export([
                             1280 => 720,
                             640  => 480,
                         ])
                         ->getData();

        $parent = Arr::get($content, 'parents.0');
        $resolutions = [
            [1280, 720],
            [640, 480],
        ];
        foreach ($content[$parent['id'].'-children'] as $key => $data) {
            $model = ResourceModel::query()->findOrFail($data['id']);
            $this->assertEquals($parent['directory'], $model->directory);
            $this->assertEquals($parent['id'], $model->parent_id);

            self::assertEquals($resolutions[$key][0], $model->options['height']);
            self::assertEquals($resolutions[$key][1], $model->options['width']);

            $this->assertDirectoryExists(alicia_storage()->path($model->directory));
            $this->assertFileExists(alicia_storage()->path($model->path));
        }
    }

    /**
     * @test
     *
     * @throws AliciaException|InvalidManipulation
     *
     * @return void
     */
    public function exportOnBatch(): void
    {
        $content = Alicia::batch(
            [
                UploadedFile::fake()
                            ->createWithContent(
                                'posty.jpg',
                                file_get_contents(__DIR__.'/../../resources/posty.jpg')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'g-eazy-freestyle.mp3',
                                file_get_contents(__DIR__.'/../../resources/G-Eazy-Break_From_LA_Freestyle.mp3')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'posty-with-sun-glass.jpg',
                                file_get_contents(__DIR__.'/../../resources/posty.jpg')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'video.mp4',
                                file_get_contents(__DIR__.'/../../resources/video.mp4')
                            ),
                UploadedFile::fake()->create('g-eazy-full-album.zip', 10230, 'application/zip'),
                'https://laravel.com/img/homepage/vapor.jpg',
            ]
        )
                         ->export()
                         ->getData();

        $parent = Arr::get($content, 'parents.0');
        $resolutions = [
            [960, 540],
            [1280, 720],
            [1920, 1080],
        ];
        foreach ($content[$parent['id'].'-children'] as $key => $data) {
            $model = ResourceModel::query()->findOrFail($data['id']);
            $this->assertEquals($parent['directory'], $model->directory);
            $this->assertEquals($parent['id'], $model->parent_id);

            self::assertEquals($resolutions[$key][0], $model->options['height']);
            self::assertEquals($resolutions[$key][1], $model->options['width']);

            $this->assertDirectoryExists(alicia_storage()->path($model->directory));
            $this->assertFileExists(alicia_storage()->path($model->path));
        }
    }

    /**
     * @test
     *
     * @throws AliciaException|InvalidManipulation
     *
     * @return void
     */
    public function exportOnBatchWithCustomResolutions(): void
    {
        $content = Alicia::batch(
            [
                UploadedFile::fake()
                            ->createWithContent(
                                'posty.jpg',
                                file_get_contents(__DIR__.'/../../resources/posty.jpg')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'g-eazy-freestyle.mp3',
                                file_get_contents(__DIR__.'/../../resources/G-Eazy-Break_From_LA_Freestyle.mp3')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'posty-with-sun-glass.jpg',
                                file_get_contents(__DIR__.'/../../resources/posty.jpg')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'video.mp4',
                                file_get_contents(__DIR__.'/../../resources/video.mp4')
                            ),
                UploadedFile::fake()->create('g-eazy-full-album.zip', 10230, 'application/zip'),
                'https://laravel.com/img/homepage/vapor.jpg',
            ]
        )
                         ->export([
                             1280 => 720,
                             640  => 480,
                         ])
                         ->getData();

        $parent = Arr::get($content, 'parents.0');
        $resolutions = [
            [1280, 720],
            [640, 480],
        ];
        foreach ($content[$parent['id'].'-children'] as $key => $data) {
            $model = ResourceModel::query()->findOrFail($data['id']);
            $this->assertEquals($parent['directory'], $model->directory);
            $this->assertEquals($parent['id'], $model->parent_id);

            self::assertEquals($resolutions[$key][0], $model->options['height']);
            self::assertEquals($resolutions[$key][1], $model->options['width']);

            $this->assertDirectoryExists(alicia_storage()->path($model->directory));
            $this->assertFileExists(alicia_storage()->path($model->path));
        }
    }
}
