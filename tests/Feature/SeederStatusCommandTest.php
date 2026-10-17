<?php

namespace Thetheago\SeederVersioning\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command;
use Thetheago\SeederVersioning\Services\SeederVersioningService;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederStatusCommandTest extends TestCase
{
    public function test_commandShouldListNewSeedersWithoutRunningThem(): void
    {
        $hashes = $this->currentHashes();

        $this->artisan('seed:status')
            ->expectsOutput('No seeders have been run yet.')
            ->expectsTable(['Seeder', 'Status', 'Hash'], [
                ['ProductsSeeder', 'new', substr($hashes['ProductsSeeder'], 0, 12)],
                ['UserSeeder', 'new', substr($hashes['UserSeeder'], 0, 12)],
            ])
            ->expectsOutputToContain('2 seeders pending.')
            ->assertExitCode(0);

        $this->assertFalse(Schema::hasTable('seeder_versions'));
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_commandShouldShowLastSeederRunAndNothingPending(): void
    {
        $service = app(SeederVersioningService::class);
        $service->runSeederVersionMigration();
        $service->runVersionedSeeders(true);

        DB::table('seeder_versions')->where('seeder', 'UserSeeder')->update(['ran_at' => '2030-01-01 10:00:00']);
        $hash = DB::table('seeder_versions')->where('seeder', 'UserSeeder')->value('hash');

        $this->artisan('seed:status')
            ->expectsOutput('Last seeder run: UserSeeder (' . substr($hash, 0, 12) . ') at 2030-01-01 10:00:00')
            ->expectsOutput('No new or modified seeders.')
            ->doesntExpectOutputToContain('pending')
            ->assertExitCode(0);
    }

    public function test_commandShouldListModifiedSeeders(): void
    {
        $service = app(SeederVersioningService::class);
        $service->runSeederVersionMigration();
        $service->runVersionedSeeders(true);

        DB::table('seeder_versions')->where('seeder', 'UserSeeder')->update(['hash' => str_repeat('0', 64)]);

        $this->artisan('seed:status')
            ->expectsTable(['Seeder', 'Status', 'Hash'], [
                ['UserSeeder', 'modified', substr($this->currentHashes()['UserSeeder'], 0, 12)],
            ])
            ->expectsOutputToContain('1 seeder pending.')
            ->assertExitCode(0);
    }

    public function test_commandShouldListUpToDateSeedersWithAllOption(): void
    {
        $service = app(SeederVersioningService::class);
        $service->runSeederVersionMigration();
        $service->runVersionedSeeders(true);

        DB::table('seeder_versions')->where('seeder', 'UserSeeder')->update(['hash' => str_repeat('0', 64)]);
        $hashes = $this->currentHashes();

        $this->artisan('seed:status', ['--all' => true])
            ->expectsTable(['Seeder', 'Status', 'Hash'], [
                ['ProductsSeeder', 'up to date', substr($hashes['ProductsSeeder'], 0, 12)],
                ['UserSeeder', 'modified', substr($hashes['UserSeeder'], 0, 12)],
            ])
            ->expectsOutputToContain('1 seeder pending.')
            ->assertExitCode(0);
    }

    public function test_commandShouldFailWhenSeedersDirectoryIsMissing(): void
    {
        config()->set('seeder-versioning.path', '/path/that/does/not/exist');

        $this->artisan('seed:status')
            ->expectsOutput('⚠️  Seeders directory not found at: /path/that/does/not/exist')
            ->assertExitCode(Command::FAILURE);
    }

    /**
     * @return array<string, string>
     */
    private function currentHashes(): array
    {
        return array_column(app(SeederVersioningService::class)->seederStatuses(), 'hash', 'seeder');
    }
}
