<?php

namespace Thetheago\SeederVersioning\Tests\Unit;

use Thetheago\SeederVersioning\Facades\SeederVersioning;
use Thetheago\SeederVersioning\Tests\Database\Seeders\UserSeeder;
use Thetheago\SeederVersioning\Tests\TestCase;

class SeederVersioningServiceTest extends TestCase
{
    public function test_ServiceCanRunSeeder(): void
    {
        SeederVersioning::runSeederVersionMigration();
        SeederVersioning::runSeeder(UserSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'spongebob@gmail.com']);
    }
}
