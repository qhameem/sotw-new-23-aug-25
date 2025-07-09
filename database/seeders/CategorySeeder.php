<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Productivity', 'description' => 'Tools to help you get things done.'],
            ['name' => 'Design', 'description' => 'Design tools and resources.'],
            ['name' => 'Developer Tools', 'description' => 'Tools for developers.'],
            ['name' => 'SaaS', 'description' => 'Software as a Service products.'],
            ['name' => 'Solo Developer', 'description' => 'Products built by solo developers.'],
            ['name' => 'Vibe-coded', 'description' => 'Products with a unique or fun vibe.'],
            ['name' => 'Monthly Subscription', 'description' => 'Products with a monthly payment model.'],
            ['name' => 'Lifetime Deal', 'description' => 'Products with a one-time payment.'],
            ['name' => 'Marketing', 'description' => 'Marketing tools and platforms.'],
            ['name' => 'AI & Machine Learning', 'description' => 'Artificial Intelligence and ML products.'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
