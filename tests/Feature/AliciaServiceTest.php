<?php

namespace Hans\Alicia\Tests\Feature;

    use Hans\Alicia\Facades\Alicia;
    use Hans\Alicia\Tests\TestCase;

    class AliciaServiceTest extends TestCase
    {
        /**
         * @test
         *
         * @return void
         */
        public function getData(): void
        {
            $data = Alicia::getData();

            self::assertNull($data);
        }
    }
