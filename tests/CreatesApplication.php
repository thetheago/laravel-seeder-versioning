<?php

namespace Thetheago\SeederVersioning\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Thetheago\SeederVersioning\SeederVersioningServiceProvider;
use Thetheago\SeederVersioning\Tests\Database\Migrations\ProductsMigration;
use Thetheago\SeederVersioning\Tests\Database\Migrations\UsersMigration;

class CreatesApplication extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SeederVersioningServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('Database.default', 'sqlite');
        $app['config']->set('Database.connections.sqlite', [
            'driver' => 'sqlite',
            'Database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('seeder-versioning.path', __DIR__ . '/Database/Seeders');
        $app['config']->set('seeder-versioning.table', 'seeder_versions');
    }

    protected function runTestMigrations(): void
    {
        (new UsersMigration())->up();
        (new ProductsMigration())->up();
    }
}
