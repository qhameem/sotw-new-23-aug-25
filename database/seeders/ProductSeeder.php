<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::pluck('id')->toArray();
        $users = User::pluck('id')->toArray();
        for ($i = 1; $i <= 20; $i++) {
            $name = 'Product ' . $i;
            $product = Product::create([
                'user_id' => $users[array_rand($users)],
                'name' => $name,
                'slug' => Str::slug($name . '-' . uniqid()),
                'tagline' => 'This is the tagline for ' . $name,
                'product_page_tagline' => 'This is the page tagline for ' . $name,
                'description' => 'Description for ' . $name . '. Lorem ipsum dolor sit amet.',
                'link' => 'https://example.com/product' . $i,
                // 'category_id' => $categories[array_rand($categories)], // Removed
                'votes_count' => rand(0, 100),
                'logo' => 'https://placehold.co/150x150/0000FF/808080/png?text=Product%20' . $i, // Using a different placeholder service
                'approved' => true, // Assuming products are approved by default for seeding
                'published_at' => now(), // Assuming products are published by default for seeding
            ]);

            // Attach 1 to 3 random categories
            $randomCategories = array_rand(array_flip($categories), rand(1, min(3, count($categories))));
            if (!is_array($randomCategories)) {
                $randomCategories = [$randomCategories];
            }
            $product->categories()->attach($randomCategories);
        }
    }
}
