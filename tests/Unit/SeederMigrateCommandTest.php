<?php

namespace Thetheago\SeederVersioning\Tests\Unit;

use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Console\Command\Command;
use Thetheago\SeederVersioning\Services\SeederVersioningService;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederMigrateCommandTest extends TestCase
{
    private const HEADERS = ['Seeder', 'Status', 'Hash', 'Risks'];

    private const PENDING_ROWS = [
        ['UserSeeder', 'modified', '3f9a1c', '[high] truncate() on table users'],
        ['ProductSeeder', 'new', 'a71e02', '[medium] insert() without upsert'],
    ];

    private const QUESTION = '2 seeders, 1 high risk will be run. Do you want to continue?';

    public function test_commandShouldShowPreviewAndRunAfterConfirmation(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('runVersionedSeeders')->once()->with(false);

        $this->artisan('seed:migrate')
            ->expectsTable(self::HEADERS, self::PENDING_ROWS)
            ->expectsConfirmation(self::QUESTION, 'yes')
            ->expectsOutput('Running seeders...')
            ->expectsOutput('Done.')
            ->assertExitCode(0);
    }

    public function test_commandShouldNotRunSeedersWhenConfirmationIsDenied(): void
    {
        $service = $this->mockService();
        $service->shouldNotReceive('runVersionedSeeders');

        $this->artisan('seed:migrate')
            ->expectsConfirmation(self::QUESTION, 'no')
            ->expectsOutputToContain('Command cancelled.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_commandShouldRunInHashOnlyMode(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('runVersionedSeeders')->once()->with(true);

        $this->artisan('seed:migrate', ['--hash-only' => true])
            ->expectsConfirmation('2 seeders, 1 high risk will be registered without running (hash-only). Do you want to continue?', 'yes')
            ->expectsOutput('Generating seeders hashing (hash-only).')
            ->expectsOutput('Done.')
            ->assertExitCode(0);
    }

    public function test_commandShouldOnlyShowPreviewWithPretendOption(): void
    {
        $service = $this->mockService(ensureSetup: false);
        $service->shouldNotReceive('runVersionedSeeders');

        $this->artisan('seed:migrate', ['--pretend' => true])
            ->expectsTable(self::HEADERS, self::PENDING_ROWS)
            ->expectsOutput('2 seeders, 1 high risk would be run.')
            ->doesntExpectOutput('Done.')
            ->assertExitCode(0);
    }

    public function test_commandShouldNotAskWhenNothingIsPending(): void
    {
        $service = $this->mockService(statuses: [$this->upToDateStatus()]);
        $service->shouldNotReceive('runVersionedSeeders');

        $this->artisan('seed:migrate')
            ->expectsOutput('No new or modified seeders.')
            ->assertExitCode(0);
    }

    public function test_commandShouldRunWithoutAskingWhenNotInteractive(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('runVersionedSeeders')->once()->with(false);

        $this->artisan('seed:migrate', ['--no-interaction' => true])
            ->expectsTable(self::HEADERS, self::PENDING_ROWS)
            ->expectsOutput('Done.')
            ->assertExitCode(0);
    }

    public function test_commandShouldAskForConfirmationInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $service = $this->mockService();
        $service->shouldReceive('runVersionedSeeders')->once()->with(false);

        $this->artisan('seed:migrate')
            ->expectsOutputToContain('APPLICATION IN PRODUCTION')
            ->expectsConfirmation(self::QUESTION, 'yes')
            ->expectsOutput('Done.')
            ->assertExitCode(0);
    }

    public function test_commandShouldBeCancelledInProductionWhenNotInteractive(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $service = $this->mockService();
        $service->shouldNotReceive('runVersionedSeeders');

        $this->artisan('seed:migrate', ['--no-interaction' => true])
            ->expectsOutputToContain('Command cancelled.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_commandShouldNotAskForConfirmationWithForceOption(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $service = $this->mockService();
        $service->shouldReceive('runVersionedSeeders')->once()->with(false);

        $this->artisan('seed:migrate', ['--force' => true])
            ->expectsTable(self::HEADERS, self::PENDING_ROWS)
            ->doesntExpectOutput(self::QUESTION)
            ->expectsOutput('Done.')
            ->assertExitCode(0);
    }

    public function test_commandShouldFailWhenEnsureSetupThrowsException(): void
    {
        $serviceMock = Mockery::mock(SeederVersioningService::class);
        $serviceMock->shouldReceive('ensureSetup')
            ->once()
            ->andThrow(new \RuntimeException('Seeder table missing.'));

        $serviceMock->shouldNotReceive('runVersionedSeeders');

        $this->app->instance(SeederVersioningService::class, $serviceMock);

        $this->artisan('seed:migrate')
            ->expectsOutput("⚠️  Seeder table missing.\n")
            ->assertExitCode(Command::FAILURE);
    }

    private function mockService(?array $statuses = null, bool $ensureSetup = true): MockInterface
    {
        $serviceMock = Mockery::mock(SeederVersioningService::class);

        if ($ensureSetup) {
            $serviceMock->shouldReceive('ensureSetup')->once();
        } else {
            $serviceMock->shouldNotReceive('ensureSetup');
        }

        $serviceMock->shouldReceive('seederStatuses')->once()->andReturn($statuses ?? [
            [
                'seeder' => 'UserSeeder',
                'status' => SeederVersioningService::STATUS_MODIFIED,
                'hash' => '3f9a1c' . str_repeat('0', 58),
                'risks' => [['level' => 'high', 'message' => 'truncate() on table users']],
            ],
            [
                'seeder' => 'ProductSeeder',
                'status' => SeederVersioningService::STATUS_NEW,
                'hash' => 'a71e02' . str_repeat('0', 58),
                'risks' => [['level' => 'medium', 'message' => 'insert() without upsert']],
            ],
            $this->upToDateStatus(),
        ]);

        $this->app->instance(SeederVersioningService::class, $serviceMock);

        return $serviceMock;
    }

    private function upToDateStatus(): array
    {
        return [
            'seeder' => 'RoleSeeder',
            'status' => SeederVersioningService::STATUS_UP_TO_DATE,
            'hash' => '9c0b44' . str_repeat('0', 58),
            'risks' => [],
        ];
    }
}
