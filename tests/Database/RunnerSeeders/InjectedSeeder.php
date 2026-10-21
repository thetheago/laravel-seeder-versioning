<?php

namespace Thetheago\SeederVersioning\Tests\Database\RunnerSeeders;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Seeder;

class InjectedSeeder extends Seeder
{
    public function run(DatabaseManager $db): void
    {
        $db->table('users')->insert(['name' => 'Injected', 'email' => 'injected@example.com']);
    }
}
