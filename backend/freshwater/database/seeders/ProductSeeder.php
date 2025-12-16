<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\products\HomeProductsSeeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            HomeProductsSeeder::class
        ]);
    }
}
