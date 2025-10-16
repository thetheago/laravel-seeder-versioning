<?php

namespace Thetheago\SeederVersioning\Tests\Feature;

use Mockery;
use Thetheago\SeederVersioning\Facades\SeederVersioning;
use Illuminate\Support\Facades\File;
use Thetheago\SeederVersioning\Services\SeederRunner;
use Thetheago\SeederVersioning\Services\SeederVersioningService;
use Thetheago\SeederVersioning\Tests\Database\Seeders\UserSeeder;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederVersioningServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_runVersionedSeedersWithSuccess(): void
    {
        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders();

        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'ProductsSeeder']);
        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'UserSeeder']);

        $this->assertDatabaseHas('users', ['email' => 'spongebob@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'mike@maluco.com']);
        $this->assertDatabaseHas('products', ['name' => 'coke']);
        $this->assertDatabaseHas('products', ['name' => 'guitar']);
    }

    public function test_runVersionedSeedersHashOnlyWithSuccess(): void
    {
        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders(true);

        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'ProductsSeeder']);
        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'UserSeeder']);

        $this->assertDatabaseMissing('users', ['email' => 'spongebob@gmail.com']);
        $this->assertDatabaseMissing('users', ['email' => 'mike@maluco.com']);
        $this->assertDatabaseMissing('products', ['name' => 'coke']);
        $this->assertDatabaseMissing('products', ['name' => 'guitar']);
    }

    public function test_ensureSameHashedSeederDoesntRunTwice(): void
    {
        // TODO
//        $seederRunnerMock = Mockery::mock(SeederRunner::class);
//
//        $seederVersioning = new SeederVersioningService($this->app, $seederRunnerMock);
//        $seederVersioning->runSeederVersionMigration();
//        $seederVersioning->runVersionedSeeders();
//
//        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'ProductsSeeder']);
//        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'UserSeeder']);
//
//        $this->assertDatabaseMissing('users', ['email' => 'spongebob@gmail.com']);
//        $this->assertDatabaseMissing('users', ['email' => 'mike@maluco.com']);
//        $this->assertDatabaseMissing('products', ['name' => 'coke']);
//        $this->assertDatabaseMissing('products', ['name' => 'guitar']);
    }
}
