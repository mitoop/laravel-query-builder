<?php

namespace Mitoop\LaravelQueryBuilder\Tests;

use Mitoop\LaravelQueryBuilder\ServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class PackageTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        date_default_timezone_set('UTC');
    }

    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.timezone', 'UTC');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
