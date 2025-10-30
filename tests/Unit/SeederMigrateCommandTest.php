<?php

namespace Thetheago\SeederVersioning\Tests\Unit;

use Mockery;
use Symfony\Component\Console\Command\Command;
use Thetheago\SeederVersioning\Services\SeederVersioningService;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederMigrateCommandTest extends TestCase
{
    public function test_commandShoulRunSuccesfully(): void
    {
        $serviceMock = Mockery::mock(SeederVersioningService::class);
        $serviceMock->shouldReceive('ensureSetup')->once();
        $serviceMock->shouldReceive('runVersionedSeeders')
            ->once()
            ->with(false);

        $this->app->instance(SeederVersioningService::class, $serviceMock);

        $this->artisan('seed:migrate')->assertExitCode(0);

    }

    public function test_commandShouldRunInHashOnlyMode(): void
    {
        $serviceMock = Mockery::mock(SeederVersioningService::class);
        $serviceMock->shouldReceive('ensureSetup')->once();
        $serviceMock->shouldReceive('runVersionedSeeders')
            ->once()
            ->with(true);

        $this->app->instance(SeederVersioningService::class, $serviceMock);

        $this->artisan('seed:migrate', ['--hash-only' => true])->assertExitCode(0);
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
}
