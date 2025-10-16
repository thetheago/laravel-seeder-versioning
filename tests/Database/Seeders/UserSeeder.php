<?php

namespace Thetheago\SeederVersioning\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            ['name' => 'Sponge', 'email' => 'spongebob@gmail.com'],
            ['name' => 'Mike maluco', 'email' => 'mike@maluco.com'],
        ]);
    }
}
