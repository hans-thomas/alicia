<?php

namespace Hans\Alicia;

use Hans\Alicia\Services\AliciaService;
use Hans\Alicia\Services\SignatureService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AliciaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('signature-service', fn () => new SignatureService(alicia_config('secret')));
        $this->app->singleton('alicia-service', AliciaService::class);

        // register FFMpeg
        $this->app->register('ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider');
        $this->app->alias('FFMpeg', 'ProtoneMedia\LaravelFFMpeg\Support\FFMpeg');
        // register ImageOptimizer
        $this->app->register('Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider');
        $this->app->alias('ImageOptimizer', 'Spatie\LaravelImageOptimizer\Facades\ImageOptimizer');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'alicia');
        $this->registerRoutes();

        config([
            'filesystems.disks' => array_merge(config('filesystems.disks'), [
                'resources' => [
                    'driver' => 'local',
                    'root'   => config('alicia.base'),
                ],
            ]),
        ]);

        config([
            'filesystems.links' => array_merge(config('filesystems.links'), [
                public_path('resources') => config('alicia.base'),
            ]),
        ]);

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__.'/../config/config.php' => config_path('alicia.php'),
                ],
                'alicia-config'
            );
            $this->loadMigrationsFrom(__DIR__.'/../migrations');
        }
    }

    /**
     * Register routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::prefix('api')->middleware('api')->group(__DIR__.'/../routes/api.php');
    }
}
