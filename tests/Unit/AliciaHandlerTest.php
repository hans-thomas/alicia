<?php

namespace Hans\Alicia\Tests\Unit;

    use Hans\Alicia\Facades\Alicia;
    use Hans\Alicia\Tests\Core\Factories\PostFactory;
    use Hans\Alicia\Tests\Core\Models\Post;
    use Hans\Alicia\Tests\TestCase;
    use Illuminate\Http\UploadedFile;

    class AliciaHandlerTest extends TestCase
    {
        private Post $post;

        /**
         * Setup the test environment.
         *
         * @return void
         */
        protected function setUp(): void
        {
            parent::setUp();

            $this->post = PostFactory::new()->create();
        }

        /**
         * @test
         *
         * @return void
         */
        public function attachments(): void
        {
            $model = Alicia::upload(
                UploadedFile::fake()->create('zipped-file.zip', 10230, 'application/zip')
            )
                           ->getData();

            $this->post->attachments()->sync($model);

            self::assertEquals(
                $model->toArray(),
                $this->post->attachments()->first()->attributesToArray()
            );
        }

        /**
         * @test
         *
         * @return void
         */
        public function attachTo(): void
        {
            $model = Alicia::upload(
                UploadedFile::fake()->create('zipped-file.zip', 10230, 'application/zip')
            )
                           ->getData();

            $result = $this->post->attachTo($model);

            self::assertEquals(
                [
                    'attached' => [1],
                    'detached' => [],
                    'updated'  => [],
                ],
                $result
            );

            self::assertEquals(
                $model->toArray(),
                $this->post->attachments()->first()->attributesToArray()
            );

            self::assertEquals(
                [
                    'resourcable_id'   => 1,
                    'resource_id'      => 1,
                    'resourcable_type' => Post::class,
                    'key'              => null,
                    'attached_at'      => now(),
                ],
                $this->post->attachment()->toArray()['pivot']
            );
        }

        /**
         * @test
         *
         * @return void
         */
        public function attachToWithKey(): void
        {
            $model = Alicia::upload(
                UploadedFile::fake()->create('zipped-file.zip', 10230, 'application/zip')
            )
                           ->getData();

            $this->post->attachTo($model, $key = 'id-card');

            self::assertEquals(
                [
                    'resourcable_id'   => 1,
                    'resource_id'      => 1,
                    'resourcable_type' => Post::class,
                    'key'              => $key,
                    'attached_at'      => now(),
                ],
                $this->post->attachment()->toArray()['pivot']
            );
        }

        /**
         * @test
         *
         * @return void
         */
        public function attachment(): void
        {
            $modelB = Alicia::external(
                'http://laravel.com/img/homepage/vapor.jpg'
            )
                            ->getData();
            $modelA = Alicia::upload(
                UploadedFile::fake()->create('zipped-file.zip', 10230, 'application/zip')
            )
                            ->getData();

            $this->post->attachTo($modelA);
            $this->post->attachTo($modelB);

            self::assertEquals(
                $modelA->toArray(),
                $this->post->attachment()->attributesToArray()
            );
        }

        /**
         * @test
         *
         * @return void
         */
        public function deleteAttachments(): void
        {
            $modelB = Alicia::upload(
                UploadedFile::fake()
                            ->createWithContent(
                                'g-eazy-freestyle.mp3',
                                file_get_contents(__DIR__.'/../resources/G-Eazy-Break_From_LA_Freestyle.mp3')
                            )
            )
                            ->getData();

            $modelA = Alicia::upload(
                UploadedFile::fake()->create('zipped-file.zip', 10230, 'application/zip')
            )
                            ->getData();

            $this->post->attachTo($modelA);
            $this->post->attachTo($modelB);

            $result = $this->post->deleteAttachments();

            self::assertEquals(
                [
                    1 => true,
                    2 => true,
                ],
                $result
            );

            self::assertFileDoesNotExist($modelA->fullPath);
            self::assertFileDoesNotExist($modelB->fullPath);
        }

        /**
         * @test
         *
         * @return void
         */
        public function attachManyTo(): void
        {
            $modelB = Alicia::upload(
                UploadedFile::fake()
                            ->createWithContent(
                                'g-eazy-freestyle.mp3',
                                file_get_contents(__DIR__.'/../resources/G-Eazy-Break_From_LA_Freestyle.mp3')
                            )
            )
                            ->getData();

            $modelA = Alicia::upload(
                UploadedFile::fake()->create('zipped-file.zip', 10230, 'application/zip')
            )
                            ->getData();

            $this->post->attachManyTo([
                $modelA->id,
                $modelB->id => ['key' => $key = 'avatar'],
            ]);

            self::assertEquals(
                [
                    'resourcable_id'   => 1,
                    'resource_id'      => 2,
                    'resourcable_type' => Post::class,
                    'key'              => null,
                    'attached_at'      => now(),
                ],
                $this->post->attachments()->get()[0]->toArray()['pivot']
            );

            self::assertEquals(
                [
                    'resourcable_id'   => 1,
                    'resource_id'      => 1,
                    'resourcable_type' => Post::class,
                    'key'              => $key,
                    'attached_at'      => now(),
                ],
                $this->post->attachments()->get()[1]->toArray()['pivot']
            );
        }
    }
