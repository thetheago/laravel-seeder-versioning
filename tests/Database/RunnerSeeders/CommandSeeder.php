<?php

namespace Thetheago\SeederVersioning\Tests\Database\RunnerSeeders;

use Illuminate\Database\Seeder;

class CommandSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Hello from CommandSeeder');
    }
}
