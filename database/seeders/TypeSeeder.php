<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Type;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Pricing', 'description' => 'How the product is priced.'],
            ['name' => 'Software Categories', 'description' => 'The category of the software.'],
            ['name' => 'Best for', 'description' => 'The audience or use case the product fits best.'],
            ['name' => 'Platform', 'description' => 'Where the product runs or is available.'],
        ];

        foreach ($types as $type) {
            Type::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
