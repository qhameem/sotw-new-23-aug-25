<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Type;
use App\Support\CategoryTypeRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UseCaseSeeder extends Seeder
{
    public function run(): void
    {
        $type = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))->first();

        if (!$type) {
            return;
        }

        $path = base_path('docs/use-cases-seed.csv');

        if (!is_file($path)) {
            return;
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);

        if (!$header) {
            return;
        }

        foreach ($rows as $row) {
            $data = array_combine($header, $row);

            if (!$data || empty($data['name'])) {
                continue;
            }

            $name = trim((string) $data['name']);
            $slug = trim((string) ($data['slug'] ?? ''));

            $category = Category::firstOrCreate(
                ['slug' => $slug !== '' ? $slug : Str::slug($name)],
                [
                    'name' => $name,
                    'description' => "Software tools for {$name}.",
                    'meta_description' => "Browse software tools for {$name}.",
                ]
            );

            $category->types()->syncWithoutDetaching([$type->id]);
        }
    }
}
