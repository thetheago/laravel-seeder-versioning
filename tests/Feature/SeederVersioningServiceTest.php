<?php

namespace Thetheago\SeederVersioning\Tests\Feature;

use Illuminate\Support\Facades\DB;
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
        $seederRunnerMock = Mockery::mock(SeederRunner::class);
        $seederRunnerMock->shouldReceive('run')->twice();

        $seederVersioning = new SeederVersioningService($this->app, $seederRunnerMock);
        $seederVersioning->runSeederVersionMigration();
        $seederVersioning->runVersionedSeeders();

        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'ProductsSeeder']);
        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'UserSeeder']);

        $seederVersioning->runSeederVersionMigration();
    }

    public function test_ensureRunWhenFileChange(): void
    {
        $seederRunner = new SeederRunner();
        $seederVersioning = new SeederVersioningService($this->app, $seederRunner);
        $seederVersioning->runSeederVersionMigration();
        $seederVersioning->runVersionedSeeders();

        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'ProductsSeeder']);
        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'UserSeeder']);
        $this->assertDatabaseCount('users', 2);

        $userSeederBeforeDetail = DB::table($this->table)->where('seeder', 'UserSeeder')->first();

        $this->changeUserSeederContentTemporary($seederVersioning);

        $userSeederAfterDetail = DB::table($this->table)->where('seeder', 'UserSeeder')->first();

        $this->assertNotEquals($userSeederBeforeDetail->hash, $userSeederAfterDetail->hash);
        $this->assertDatabaseCount('users', 4);
    }

    /**
     * @param SeederVersioningService $seederVersioning
     * @return void
     */
    private function changeUserSeederContentTemporary(SeederVersioningService $seederVersioning): void
    {
        $filePath = 'tests/Database/Seeders/UserSeeder.php';

        $originalContent = file_get_contents($filePath);
        $search = 'spongebob@gmail.com';
        $this->assertStringContainsString($search, $originalContent, "String '{$search}' não encontrada em {$filePath}");

        $mailReplace = 'spongebob+ci@example.com';
        $modifiedContent = preg_replace('/' . preg_quote($search, '/') . '/', $mailReplace, $originalContent, 1);

        try {
            file_put_contents($filePath, $modifiedContent);
            touch($filePath, time() + 1);
            clearstatcache(true, $filePath);

            $seederVersioning->runVersionedSeeders();
        } finally {
            file_put_contents($filePath, $originalContent);
            touch($filePath, time() + 1);
            clearstatcache(true, $filePath);
        }
    }
}
