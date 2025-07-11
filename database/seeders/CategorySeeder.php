<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pricingType = Type::where('name', 'Pricing')->first();
        $softwareType = Type::where('name', 'Software Categories')->first();

        $categories = [
            // Software Categories
            ['name' => 'Productivity', 'description' => 'Tools to help you get things done.', 'type' => $softwareType],
            ['name' => 'Design', 'description' => 'Design tools and resources.', 'type' => $softwareType],
            ['name' => 'Developer Tools', 'description' => 'Tools for developers.', 'type' => $softwareType],
            ['name' => 'SaaS', 'description' => 'Software as a Service products.', 'type' => $softwareType],
            ['name' => 'Solo Developer', 'description' => 'Products built by solo developers.', 'type' => $softwareType],
            ['name' => 'Vibe-coded', 'description' => 'Products with a unique or fun vibe.', 'type' => $softwareType],
            ['name' => 'Marketing', 'description' => 'Marketing tools and platforms.', 'type' => $softwareType],
            ['name' => 'AI & Machine Learning', 'description' => 'Artificial Intelligence and ML products.', 'type' => $softwareType],

            // Pricing
            ['name' => 'Monthly Subscription', 'description' => 'Products with a monthly payment model.', 'type' => $pricingType],
            ['name' => 'Lifetime Deal', 'description' => 'Products with a one-time payment.', 'type' => $pricingType],
            ['name' => 'Freemium', 'description' => 'Products with a free tier.', 'type' => $pricingType],
            ['name' => 'Pay-as-you-go', 'description' => 'Pay for what you use.', 'type' => $pricingType],
            ['name' => 'Free', 'description' => 'Completely free products.', 'type' => $pricingType],
            ['name' => 'Open Source', 'description' => 'Open source projects.', 'type' => $pricingType],
        ];

        foreach ($categories as $cat) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                ]
            );

            if ($cat['type']) {
                $category->types()->syncWithoutDetaching([$cat['type']->id]);
            }
        }
    }
}
