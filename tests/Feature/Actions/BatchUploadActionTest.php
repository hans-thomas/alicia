<?php

namespace Hans\Alicia\Tests\Feature\Actions;

use Hans\Alicia\Facades\Alicia;
use Hans\Alicia\Tests\TestCase;
use Illuminate\Http\UploadedFile;

class BatchUploadActionTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function batchUpload()
    {
        config()->set('alicia.hls.enable', true);
        $data = Alicia::batch(
            [
                UploadedFile::fake()
                            ->createWithContent(
                                'video.mp4',
                                file_get_contents(__DIR__.'/../../resources/video.mp4')
                            ),
                UploadedFile::fake()->image('eminem.png', 512, 512),
                UploadedFile::fake()->create('ziped-file.zip', 10230, 'application/zip'),
                UploadedFile::fake()
                            ->createWithContent(
                                'g-eazy-freestyle.mp3',
                                file_get_contents(__DIR__.'/../../resources/G-Eazy-Break_From_LA_Freestyle.mp3')
                            ),
                $url = 'http://laravel.com/img/homepage/vapor.jpg',
            ]
        )
                      ->getData();

        $video = $data[0];

        $this->assertDirectoryExists(alicia_storage()->path($video->directory.'/hls'));
        $this->assertFileExists(alicia_storage()->path($video->directory.'/'.$video->hls));

        $this->assertDirectoryExists(alicia_storage()->path($video->directory));
        $this->assertFileExists(alicia_storage()->path($video->path));

        $image = $data[1];

        $this->assertDirectoryExists(alicia_storage()->path($image->directory));
        $this->assertFileExists(alicia_storage()->path($image->path));

        $file = $data[2];

        $this->assertDirectoryExists(alicia_storage()->path($file->directory));
        $this->assertFileExists(alicia_storage()->path($file->path));

        $audio = $data[3];

        $this->assertDirectoryExists(alicia_storage()->path($audio->directory));
        $this->assertFileExists(alicia_storage()->path($audio->path));

        $link = $data[4];

        $this->assertStringEqualsStringIgnoringLineEndings(
            $url,
            $link->link
        );
    }
}
