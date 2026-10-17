<?php

namespace Thetheago\SeederVersioning\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Thetheago\SeederVersioning\Tests\Database\Seeders\ProductsSeeder;
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

    public function test_runVersionedSeedersFollowsConfiguredOrder(): void
    {
        config(['seeder-versioning.order' => [UserSeeder::class]]);

        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders(true);

        $this->assertEquals(
            ['UserSeeder', 'ProductsSeeder'],
            DB::table('seeder_versions')->orderBy('id')->pluck('seeder')->all()
        );
    }

    public function test_ensureSameHashedSeederDoesntRunTwice(): void
    {
        $seederRunner = new SeederRunner();
        $seederVersioning = new SeederVersioningService($seederRunner);
        $seederVersioning->runSeederVersionMigration();
        $seederVersioning->runVersionedSeeders();

        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'ProductsSeeder']);
        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'UserSeeder']);
        $this->assertDatabaseCount('users', 2);
        $userSeederBeforeDetail = DB::table($this->table)->where('seeder', 'UserSeeder')->first();
        $seederVersioning->runVersionedSeeders();
        $userSeederAfterDetail = DB::table($this->table)->where('seeder', 'UserSeeder')->first();
        $this->assertEquals($userSeederBeforeDetail->hash, $userSeederAfterDetail->hash);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_ensureRunWhenFileChange(): void
    {
        $seederRunner = new SeederRunner();
        $seederVersioning = new SeederVersioningService($seederRunner);
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

    public function test_historyRecordsNewSeedersWithoutPreviousHash(): void
    {
        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders();

        $history = DB::table('seeder_version_history')->where('seeder', 'UserSeeder')->get();
        $current = DB::table('seeder_versions')->where('seeder', 'UserSeeder')->first();

        $this->assertCount(1, $history);
        $this->assertEquals($current->hash, $history[0]->hash);
        $this->assertNull($history[0]->previous_hash);
        $this->assertEquals(0, $history[0]->hash_only);
    }

    public function test_historyKeepsEveryChangeAndSkipsUnchangedRuns(): void
    {
        $seederVersioning = new SeederVersioningService(new SeederRunner());
        $seederVersioning->runSeederVersionMigration();
        $seederVersioning->runVersionedSeeders();
        $seederVersioning->runVersionedSeeders();

        $this->assertEquals(1, DB::table('seeder_version_history')->where('seeder', 'UserSeeder')->count());

        $originalHash = DB::table('seeder_versions')->where('seeder', 'UserSeeder')->value('hash');

        $this->changeUserSeederContentTemporary($seederVersioning);

        $history = DB::table('seeder_version_history')->where('seeder', 'UserSeeder')->orderBy('id')->get();

        $this->assertCount(2, $history);
        $this->assertEquals($originalHash, $history[1]->previous_hash);
        $this->assertEquals(
            DB::table('seeder_versions')->where('seeder', 'UserSeeder')->value('hash'),
            $history[1]->hash
        );
    }

    public function test_historyMarksHashOnlyRuns(): void
    {
        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders(true);

        $this->assertEquals(2, DB::table('seeder_version_history')->where('hash_only', true)->count());
    }

    public function test_ensureSetupCreatesHistoryTableWhenVersionsTableAlreadyExists(): void
    {
        Artisan::call('migrate', [
            '--path' => __DIR__ . '/../../database/migrations/2025_01_01_000000_create_seeder_versions_table.php',
            '--realpath' => true,
        ]);
        $this->assertTrue(Schema::hasTable('seeder_versions'));
        $this->assertFalse(Schema::hasTable('seeder_version_history'));

        File::partialMock()->shouldReceive('exists')->with(config_path('seeder-versioning.php'))->andReturn(true);
        app(SeederVersioningService::class)->ensureSetup();

        $this->assertTrue(Schema::hasTable('seeder_version_history'));
    }

    public function test_databaseSeederIsExcludedByDefault(): void
    {
        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders();

        $this->assertDatabaseMissing('seeder_versions', ['seeder' => 'DatabaseSeeder']);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_databaseSeederRunsWhenNotExcluded(): void
    {
        config()->set('seeder-versioning.except', []);

        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders();

        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'DatabaseSeeder']);
        $this->assertDatabaseCount('users', 4);
    }

    public function test_seederExcludedByFullyQualifiedNameIsSkipped(): void
    {
        config()->set('seeder-versioning.except', ['DatabaseSeeder', ProductsSeeder::class]);

        app(SeederVersioningService::class)->runSeederVersionMigration();
        app(SeederVersioningService::class)->runVersionedSeeders();

        $this->assertDatabaseMissing('seeder_versions', ['seeder' => 'ProductsSeeder']);
        $this->assertDatabaseHas('seeder_versions', ['seeder' => 'UserSeeder']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_seederStatusesFollowsOrderAndSkipsExcludedSeeders(): void
    {
        config(['seeder-versioning.order' => [UserSeeder::class]]);

        $statuses = app(SeederVersioningService::class)->seederStatuses();

        $this->assertEquals(['UserSeeder', 'ProductsSeeder'], array_column($statuses, 'seeder'));
        $this->assertEquals(
            [SeederVersioningService::STATUS_NEW, SeederVersioningService::STATUS_NEW],
            array_column($statuses, 'status')
        );
    }

    public function test_seederStatusesDetectsModifiedAndUpToDateSeeders(): void
    {
        $service = app(SeederVersioningService::class);
        $service->runSeederVersionMigration();
        $service->runVersionedSeeders(true);

        DB::table('seeder_versions')->where('seeder', 'UserSeeder')->update(['hash' => str_repeat('0', 64)]);

        $this->assertEquals(
            ['ProductsSeeder' => SeederVersioningService::STATUS_UP_TO_DATE, 'UserSeeder' => SeederVersioningService::STATUS_MODIFIED],
            array_column($service->seederStatuses(), 'status', 'seeder')
        );
    }

    public function test_lastRanSeederReturnsTheMostRecentOne(): void
    {
        $service = app(SeederVersioningService::class);

        $this->assertNull($service->lastRanSeeder());

        $service->runSeederVersionMigration();
        $service->runVersionedSeeders(true);

        DB::table('seeder_versions')->where('seeder', 'ProductsSeeder')->update(['ran_at' => '2030-01-01 10:00:00']);

        $this->assertEquals('ProductsSeeder', $service->lastRanSeeder()->seeder);
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
