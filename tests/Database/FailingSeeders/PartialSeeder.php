<?php

namespace Thetheago\SeederVersioning\Tests\Database\FailingSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartialSeeder extends Seeder
{
    /**
     * Writes a row and then fails, to check that the row is rolled back.
     */
    public function run(): void
    {
        DB::table('users')->insert(['name' => 'Partial', 'email' => 'partial@example.com']);

        throw new RuntimeException('Something went wrong');
    }
}
