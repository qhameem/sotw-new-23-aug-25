<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductImpressionsColumnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = \App\Models\Product::all();

        foreach ($products as $product) {
            \Illuminate\Support\Facades\DB::table('products')
                ->where('id', $product->id)
                ->update(['impressions' => rand(100, 1000)]);
        }
    }
}
