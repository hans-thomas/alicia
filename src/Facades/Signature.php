<?php

namespace Hans\Alicia\Facades;

    use Hans\Alicia\Services\SignatureService;
    use Illuminate\Support\Facades\Facade;

    /**
     * @method static string create()
     * @method static string key()
     * @method static bool   isNotValid( string $signature )
     * @method static bool   isValid( string $signature )
     *
     * @see SignatureService
     */
    class Signature extends Facade
    {
        /**
         * Get the registered name of the component.
         *
         * @throws \RuntimeException
         *
         * @return string
         */
        protected static function getFacadeAccessor()
        {
            return 'signature-service';
        }
    }
