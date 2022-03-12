<?php

	namespace Hans\Alicia\Tests;

	use Hans\Alicia\AliciaServiceProvider;
	use Hans\Alicia\Contracts\AliciaContract;
	use Hans\Alicia\Contracts\SignatureContract;
	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Foundation\Application;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Routing\Router;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Facades\Storage;
	use Orchestra\Testbench\TestCase as BaseTestCase;

	class TestCase extends BaseTestCase {
		use RefreshDatabase;

		public AliciaContract $alicia;
		public SignatureContract $signature;
		public Filesystem $storage;
		private array $config;

		public function getConfig( string $key, $default ) {
			return Arr::get( $this->config, $key, $default );
		}

		/**
		 * Setup the test environment.
		 */
		protected function setUp(): void {
			// Code before application created.

			parent::setUp();

			// Code after application created.
			$this->config    = config( 'alicia' );
			$this->alicia    = app( AliciaContract::class );
			$this->signature = app( SignatureContract::class );
			$this->storage   = Storage::disk( 'resources' );
		}

		/**
		 * Get application timezone.
		 *
		 * @param Application $app
		 *
		 * @return string|null
		 */
		protected function getApplicationTimezone( $app ) {
			return 'UTC';
		}

		/**
		 * Get package providers.
		 *
		 * @param Application $app
		 *
		 * @return array
		 */
		protected function getPackageProviders( $app ) {
			return [
				AliciaServiceProvider::class
			];
		}

		/**
		 * Override application aliases.
		 *
		 * @param Application $app
		 *
		 * @return array
		 */
		protected function getPackageAliases( $app ) {
			return [//	'Acme' => 'Acme\Facade',
			];
		}

		/**
		 * Define environment setup.
		 *
		 * @param Application $app
		 *
		 * @return void
		 */
		protected function defineEnvironment( $app ) {
			// Setup default database to use sqlite :memory:
			$app[ 'config' ]->set( 'database.default', 'testbench' );
			$app[ 'config' ]->set( 'database.connections.testbench', [
				'driver'   => 'sqlite',
				'database' => ':memory:',
				'prefix'   => '',
			] );
		}

		/**
		 * Define routes setup.
		 *
		 * @param Router $router
		 *
		 * @return void
		 */
		protected function defineRoutes( $router ) {
			$router->post( '/upload/{field}', function( string $field ) {
				return response()->json( $this->alicia->upload( $field )->getData(), 201 );
			} )->name( 'alicia.test.upload' );

			$router->post( '/export/{field}', function( string $field ) {
				return response()->json( $this->alicia->upload( $field )->export()->getData(), 201 );
			} )->name( 'alicia.test.upload.export' );

			$router->post( '/external/{field}', function( string $field ) {
				return response()->json( $this->alicia->external( $field )->getData(), 201 );
			} )->name( 'alicia.test.external' );

			$router->post( '/batch/{field}', function( string $field ) {
				return response()->json( $this->alicia->batch( $field )->getData(), 201 );
			} )->name( 'alicia.test.batch' );
		}

	}