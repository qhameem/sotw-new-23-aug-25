<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Type;
use App\Support\BroadUseCases;
use App\Support\CategoryTypeRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BroadUseCaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $type = Type::firstOrCreate(['name' => CategoryTypeRegistry::primaryNameFor(CategoryTypeRegistry::USE_CASE)]);

            foreach (BroadUseCases::GROUPS as $slug => [$name]) {
                $category = Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
                $category->types()->syncWithoutDetaching([$type->id]);
            }
        });
    }
}
