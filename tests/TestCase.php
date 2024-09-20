<?php

namespace Hans\Alicia\Tests;

use Hans\Alicia\AliciaServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON;'); // It's disabled in Sqlite by default
        $this->loadMigrationsFrom(__DIR__.'/Core/migrations');
    }

    /**
     * Get application timezone.
     *
     * @param Application $app
     *
     * @return string|null
     */
    protected function getApplicationTimezone($app)
    {
        return 'UTC';
    }

    /**
     * Get package providers.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            AliciaServiceProvider::class,
        ];
    }

    /**
     * Override application aliases.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
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
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Define routes setup.
     *
     * @param Router $router
     *
     * @return void
     */
    protected function defineRoutes($router)
    {
            //
    }
}
