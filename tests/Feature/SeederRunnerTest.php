<?php

namespace Thetheago\SeederVersioning\Tests\Feature;

use Thetheago\SeederVersioning\Services\SeederRunner;
use Thetheago\SeederVersioning\Tests\Database\Seeders\UserSeeder;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederRunnerTest extends TestCase
{
    public function test_serviceCanRunSeeder(): void
    {
        SeederRunner::run(UserSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'spongebob@gmail.com']);
    }
}
