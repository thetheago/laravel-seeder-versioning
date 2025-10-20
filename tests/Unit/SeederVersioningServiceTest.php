<?php

namespace Thetheago\SeederVersioning\Tests\Unit;

use Mockery;
use Thetheago\SeederVersioning\Facades\SeederVersioning;
use Illuminate\Support\Facades\File;
use Thetheago\SeederVersioning\Services\SeederRunner;
use Thetheago\SeederVersioning\Services\SeederVersioningService;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederVersioningServiceTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_ensureSetupTrhwoExceptionIfWasNotConfigFIle(): void
    {
        File::shouldReceive('exists')->andReturn(false);

        $this->expectException(\RuntimeException::class);
        SeederVersioning::ensureSetup();
    }

    public function test_ensureSetupCreateSeederVersioningTable(): void
    {
        File::shouldReceive('exists')->andReturn(true);
        SeederVersioning::ensureSetup();

        $this->assertTrue(true);
    }

    public function test_runVersionedSeedersWithSuccess(): void
    {
        $seederRunnerMock = Mockery::mock(SeederRunner::class);
        $seederRunnerMock->shouldReceive('run')->twice();

        $seederVersioning = new SeederVersioningService($seederRunnerMock);
        $seederVersioning->runSeederVersionMigration();
        $seederVersioning->runVersionedSeeders();
        $this->assertTrue(true);
    }

    public function test_runVersionedSeedersHashOnlyWithSuccess(): void
    {
        $seederRunnerMock = Mockery::mock(SeederRunner::class);
        $seederRunnerMock->shouldNotReceive('run');

        $seederVersioning = new SeederVersioningService($seederRunnerMock);
        $seederVersioning->runSeederVersionMigration();
        $seederVersioning->runVersionedSeeders(true);
        $this->assertTrue(true);
    }

    public function test_resolveSeederClassShouldThrowExceptionWhenNoNamespaceFound(): void
    {
        $seederRunnerMock = Mockery::mock(SeederRunner::class);
        $seederRunnerMock->shouldNotReceive('run');
        $seederVersioning = new SeederVersioningService($seederRunnerMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No namespace found in file: '. __DIR__ . '/Stubs/ClassWithoutNamespace.php');

        $files = File::files(__DIR__ . '/Stubs');
        $seederVersioning->resolveSeederClass($files[1]);
    }

    public function test_resolveSeederClassShouldThrowExceptionWhenNoClassnameFound() : void
    {
        $seederRunnerMock = Mockery::mock(SeederRunner::class);
        $seederRunnerMock->shouldNotReceive('run');
        $seederVersioning = new SeederVersioningService($seederRunnerMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No class found in file: '. __DIR__ . '/Stubs/ClassWithoutClassName.php');

        $files = File::files(__DIR__ . '/Stubs');
        $seederVersioning->resolveSeederClass($files[0]);
    }

    public function test_runVersionedSeedersShouldSkipIfSeedersDirDoesNotExists(): void
    {
        $this->app['config']->set('seeder-versioning.path', __DIR__ . '/Stubs/NonExistentPath');

        $seederRunnerMock = Mockery::mock(SeederRunner::class);
        $seederRunnerMock->shouldNotReceive('run');
        $seederVersioning = new SeederVersioningService($seederRunnerMock);

        $seederVersioning->runVersionedSeeders();

        $this->assertTrue(true);
    }
}
