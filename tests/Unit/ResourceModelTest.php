<?php

namespace Hans\Alicia\Tests\Unit;

use Hans\Alicia\Facades\Alicia;
use Hans\Alicia\Facades\Signature;
use Hans\Alicia\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Spatie\Image\Exceptions\InvalidManipulation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceModelTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function urlAsExternal(): void
    {
        $model = Alicia::external($link = 'https://laravel.com/img/homepage/vapor.jpg')->getData();

        $this->assertStringEqualsStringIgnoringLineEndings($link, $model->link);

        $url = route('alicia.download', [$model]);

        $this->assertStringEqualsStringIgnoringLineEndings($url, $model->downloadUrl);
    }

    /**
     * @test
     *
     * @return void
     */
    public function urlAsSignedExternal(): void
    {
        config()->set('alicia.signed', true);
        $model = Alicia::external($link = 'https://laravel.com/img/homepage/vapor.jpg')->getData();

        $this->assertStringEqualsStringIgnoringLineEndings($link, $model->link);

        $url = URL::temporarySignedRoute(
            'alicia.download',
            now()->addMinutes(alicia_config('expiration', '30')),
            [
                'resource' => $model->id,
                'hash'     => Signature::create(),
            ]
        );
        $this->assertStringEqualsStringIgnoringLineEndings($url, $model->downloadUrl);
    }

    /**
     * @test
     *
     * @return void
     */
    public function urlAsSigned(): void
    {
        config()->set('alicia.signed', true);
        $model = Alicia::upload(
            UploadedFile::fake()->image('g-eazy.png', 1080, 1080)
        )
                       ->getData();

        $this->assertEquals(
            substr(
                $link = URL::temporarySignedRoute(
                    'alicia.download',
                    now()->addMinutes(alicia_config('expiration', '30')),
                    [
                        'resource' => $model->id,
                        'hash'     => Signature::create(),
                    ]
                ),
                0,
                strpos($link, '?')
            ),
            substr($url = $model->downloadUrl, 0, strpos($url, '?'))
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function urlAsNotSigned(): void
    {
        config()->set('alicia.signed', false);

        $model = Alicia::upload(
            UploadedFile::fake()->image('g-eazy.png', 1080, 1080)
        )
                       ->getData();

        $this->assertEquals(route('alicia.download', ['resource' => $model]), $model->downloadUrl);
    }

    /**
     * @test
     *
     * @return void
     */
    public function hlsUrl(): void
    {
        config()->set('alicia.hls.enable', true);
        $model = Alicia::upload(
            UploadedFile::fake()
                        ->createWithContent(
                            'video.mp4',
                            file_get_contents(__DIR__.'/../resources/video.mp4')
                        )
        )
                       ->getData();

        $this->assertEquals(url('resources/'.$model->directory.'/'.$model->hls), $model->streamUrl);
    }

    /**
     * @test
     *
     * @return void
     */
    public function hlsUrlAsHlsDisabled(): void
    {
        config()->set('alicia.hls.enable', false);

        $model = Alicia::upload(
            UploadedFile::fake()
                        ->createWithContent(
                            'video.mp4',
                            file_get_contents(__DIR__.'/../resources/video.mp4')
                        )
        )
                       ->getData();

        $this->assertEquals(url('resources/'.$model->directory.'/'.$model->file), $model->streamUrl);
    }

    /**
     * @test
     *
     * @return void
     */
    public function hlsUrlAsAudioFile(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()
                        ->createWithContent(
                            'g-eazy-freestyle.mp3',
                            file_get_contents(__DIR__.'/../resources/G-Eazy-Break_From_LA_Freestyle.mp3')
                        )
        )
                       ->getData();

        $this->assertInstanceOf(
            BinaryFileResponse::class,
            $model->streamUrl
        );
        self::assertStringEqualsStringIgnoringLineEndings(
            $model->fullPath,
            $model->streamUrl->getFile()->getPath().'/'.$model->streamUrl->getFile()->getFilename()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function path(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()->create('ziped.file.zip', 10230, 'application/zip')
        )
                       ->getData();

        $this->assertDirectoryExists(alicia_storage()->path($model->directory));
        $this->assertStringEqualsStringIgnoringLineEndings($model->directory.'/'.$model->file, $model->path);
    }

    /**
     * @test
     *
     * @return void
     */
    public function fullPath(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()->image('g-eazy.png', 1080, 1080)
        )
                       ->getData();

        self::assertFileExists(alicia_storage()->path($model->directory));
        self::assertStringEqualsStringIgnoringLineEndings(
            alicia_storage()->path($model->path),
            $model->fullPath
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function getOptions(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()->image('g-eazy.png', 1080, 1080)
        )
                       ->getData();

        self::assertEquals(
            [
                'size'     => 3492,
                'mimeType' => 'image/png',
                'width'    => 1080,
                'height'   => 1080,
            ],
            $model->getOptions()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function updateOptions(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()->image('g-eazy.png', 1080, 1080)
        )
                       ->getData();

        self::assertEquals(
            [
                'size'     => 3492,
                'mimeType' => 'image/png',
                'width'    => 1080,
                'height'   => 1080,
            ],
            $model->getOptions()
        );

        $model->updateOptions(['size' => 2943, 'new' => 'value']);

        self::assertEquals(
            [
                'size'     => 2943,
                'mimeType' => 'image/png',
                'width'    => 1080,
                'height'   => 1080,
                'new'      => 'value',
            ],
            $model->getOptions()
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function isExternal(): void
    {
        $model = Alicia::external('https://laravel.com/img/homepage/vapor.jpg')->getData();

        self::assertTrue($model->isExternal());
        self::assertFalse($model->isNotExternal());
    }

    /**
     * @test
     *
     * @return void
     */
    public function isNotExternal(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()->image('g-eazy.png', 1080, 1080)
        )
                       ->getData();

        self::assertTrue($model->isNotExternal());
        self::assertFalse($model->isExternal());
    }

    /**
     * @test
     *
     * @throws InvalidManipulation
     *
     * @return void
     */
    public function childrenDeletedAutomatically(): void
    {
        $model = Alicia::upload(
            UploadedFile::fake()->image('g-eazy.png', 1080, 1080)
        )
                       ->export([540 => 540, 480 => 480])
                       ->getData();

        Alicia::delete($model->get('parents')->pluck('id')->first());

        self::assertEmpty(DB::table('resources')->get()->pluck('id'));
    }
}
