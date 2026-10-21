<?php

namespace Thetheago\SeederVersioning\Tests\Database\RunnerSeeders;

use Illuminate\Database\Seeder;
use Thetheago\SeederVersioning\Tests\Database\Seeders\UserSeeder;

class CallingSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
    }
}
