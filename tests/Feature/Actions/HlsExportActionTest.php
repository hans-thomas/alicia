<?php

namespace Hans\Alicia\Tests\Feature\Actions;

use Hans\Alicia\Facades\Alicia;
use Hans\Alicia\Models\Resource;
use Hans\Alicia\Tests\TestCase;
use Illuminate\Http\UploadedFile;

class HlsExportActionTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function hlsExport(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()
                        ->createWithContent(
                            'video.mp4',
                            file_get_contents(__DIR__.'/../../resources/video.mp4')
                        )
        )
                       ->hlsExport()
                       ->getData()
                       ->refresh();

        self::assertNotNull($model->hls);
        self::assertStringEndsWith('.m3u8', $model->hls);
    }

    /**
     * @test
     *
     * @return void
     */
    public function hlsExportInBatchMode(): void
    {
        $data = Alicia::batch(
            [
                UploadedFile::fake()
                            ->createWithContent(
                                'video.mp4',
                                file_get_contents(__DIR__.'/../../resources/video.mp4')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'g-eazy-freestyle.mp3',
                                file_get_contents(__DIR__.'/../../resources/G-Eazy-Break_From_LA_Freestyle.mp3')
                            ),
                UploadedFile::fake()
                            ->createWithContent(
                                'video.mp4',
                                file_get_contents(__DIR__.'/../../resources/video.mp4')
                            ),
                UploadedFile::fake()->image('eminem.png', 512, 512),
                UploadedFile::fake()->create('ziped-file.zip', 10230, 'application/zip'),
                'https://laravel.com/img/homepage/vapor.jpg',
            ]
        )
                      ->hlsExport()
                      ->getData();

        $models = $data->filter(
            fn (Resource $resource) => in_array($resource->extension, alicia_config('extensions.videos'))
        );

        self::assertCount(2, $models);

        foreach ($models as $model) {
            $model->refresh();

            self::assertNotNull($model->hls);
            self::assertStringEndsWith('.m3u8', $model->hls);
        }
    }
}
