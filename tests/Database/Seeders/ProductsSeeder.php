<?php

namespace Thetheago\SeederVersioning\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            ['name' => 'coke', 'price' => 10.89, 'quantity' => 2],
            ['name' => 'guitar', 'price' => 3150.99, 'quantity' => 43],
        ]);
    }
}
