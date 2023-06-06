<?php

	namespace Hans\Alicia\Tests;

	use Hans\Alicia\AliciaServiceProvider;
	use Hans\Alicia\Facades\Alicia;
	use Illuminate\Foundation\Application;
	use Illuminate\Foundation\Testing\RefreshDatabase;
	use Illuminate\Routing\Router;
	use Orchestra\Testbench\TestCase as BaseTestCase;

	class TestCase extends BaseTestCase {
		use RefreshDatabase;

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
				return response()->json( Alicia::upload( request()->file( $field ) )->getData(), 201 );
			} )->name( 'alicia.test.upload' );

			$router->post( '/export/{field}', function( string $field ) {
				return response()->json( Alicia::upload( request()->file( $field ) )->export()->getData(), 201 );
			} )->name( 'alicia.test.upload.export' );

			$router->post( '/external/{field}', function( string $field ) {
				return response()->json( Alicia::external( request( $field ) )->getData(), 201 );
			} )->name( 'alicia.test.external' );

			$router->post( '/batch/{field}', function( string $field ) {
				return response()->json( Alicia::batch( request( $field ) )->getData(), 201 );
			} )->name( 'alicia.test.batch' );
		}

	}