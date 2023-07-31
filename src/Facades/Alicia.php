<?php

namespace Hans\Alicia\Facades;

    use Hans\Alicia\Models\Resource;
    use Hans\Alicia\Services\AliciaService;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Collection;
    use Illuminate\Support\Facades\Facade;

    /**
     * @method static AliciaService            batch( array $files )
     * @method static AliciaService            upload( UploadedFile $file )
     * @method static AliciaService            external( string $file )
     * @method static AliciaService            export( array $resolutions = null )
     * @method static bool                     delete( Resource|int $model )
     * @method static array                    batchDelete( array $ids )
     * @method static bool                     deleteFile( string $path )
     * @method static AliciaService            makeExternal( Resource $model, string $url )
     * @method static AliciaService            fromFile( string $path )
     * @method static Resource|Collection|null getData()
     *
     * @see AliciaService
     */
    class Alicia extends Facade
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
            return 'alicia-service';
        }
    }
