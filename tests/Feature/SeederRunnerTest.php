<?php

namespace Thetheago\SeederVersioning\Tests\Feature;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Thetheago\SeederVersioning\Services\SeederRunner;
use Thetheago\SeederVersioning\Tests\Database\RunnerSeeders\CallingSeeder;
use Thetheago\SeederVersioning\Tests\Database\RunnerSeeders\CommandSeeder;
use Thetheago\SeederVersioning\Tests\Database\RunnerSeeders\InjectedSeeder;
use Thetheago\SeederVersioning\Tests\Database\Seeders\UserSeeder;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederRunnerTest extends TestCase
{
    public function test_serviceCanRunSeeder(): void
    {
        SeederRunner::run(UserSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'spongebob@gmail.com']);
    }

    public function tearDown(): void
    {
        SeederRunner::setCommand(null);
        parent::tearDown();
    }

    public function test_seederCanCallOtherSeeders(): void
    {
        SeederRunner::run(CallingSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'spongebob@gmail.com']);
    }

    public function test_dependenciesAreInjectedIntoRun(): void
    {
        SeederRunner::run(InjectedSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'injected@example.com']);
    }

    public function test_seederCanWriteThroughTheCommand(): void
    {
        $output = new BufferedOutput();
        $command = new Command();
        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(new ArrayInput([]), $output));
        SeederRunner::setCommand($command);

        SeederRunner::run(CommandSeeder::class);

        $this->assertStringContainsString('Hello from CommandSeeder', $output->fetch());
    }
}
