<?php


	namespace Hans\Alicia;


	use Hans\Alicia\Contracts\AliciaContract;
	use Hans\Alicia\Contracts\SignatureContract;
	use Illuminate\Support\Facades\Route;
	use Illuminate\Support\ServiceProvider;

	class AliciaServiceProvider extends ServiceProvider {
		/**
		 * Register any application services.
		 *
		 * @return void
		 */
		public function register() {
			$this->app->singleton( SignatureContract::class, function() {
				return new SignatureService( config( 'alicia.secret' ) );
			} );

			$this->app->singleton( AliciaContract::class, function() {
				return new AliciaService;
			} );

			// register FFMpeg
			$this->app->register( 'ProtoneMedia\LaravelFFMpeg\Support\ServiceProvider' );
			$this->app->alias( 'FFMpeg', 'ProtoneMedia\LaravelFFMpeg\Support\FFMpeg' );
			// register FFMpeg
			$this->app->register( 'Spatie\LaravelImageOptimizer\ImageOptimizerServiceProvider' );
			$this->app->alias( 'ImageOptimizer', 'Spatie\LaravelImageOptimizer\Facades\ImageOptimizer' );
		}

		/**
		 * Bootstrap any application services.
		 *
		 * @return void
		 */
		public function boot() {
			$this->mergeConfigFrom( __DIR__ . '/../config/config.php', 'alicia' );
			$this->publishes( [
				__DIR__ . '/../config/config.php' => config_path( 'alicia.php' )
			], 'alicia-config' );
			$this->loadMigrationsFrom( __DIR__ . '/../migrations' );

			config( [
				'filesystems.disks' => array_merge( config( 'filesystems.disks' ), [
					'resources' => [
						'driver' => 'local',
						'root'   => config( 'alicia.base' ),
					]
				] )
			] );

			config( [
				'filesystems.links' => array_merge( config( 'filesystems.links' ), [
					public_path( 'resources' ) => config( 'alicia.base' )
				] )
			] );


			$this->registerRoutes();
		}

		/**
		 * Define routes setup.
		 *
		 * @return void
		 */
		protected function registerRoutes() {
			Route::prefix( 'api' )->middleware( 'api' )->group( __DIR__ . '/../routes/api.php' );
		}

	}
