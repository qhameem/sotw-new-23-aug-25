<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBreadcrumbCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_prefers_a_software_category_for_breadcrumbs(): void
    {
        $categoryType = Type::create(['name' => 'Category']);
        $pricingType = Type::create(['name' => 'Pricing']);

        $softwareCategory = Category::factory()->create([
            'name' => 'Productivity',
            'slug' => 'productivity',
        ]);
        $softwareCategory->types()->attach($categoryType);

        $pricingCategory = Category::factory()->create([
            'name' => 'Subscription',
            'slug' => 'subscription',
        ]);
        $pricingCategory->types()->attach($pricingType);

        $product = Product::factory()->create([
            'name' => 'Makro',
            'slug' => 'makro',
        ]);
        $product->categories()->attach([$pricingCategory->id, $softwareCategory->id]);

        $product->load('categories.types');

        $this->assertSame($softwareCategory->id, $product->primaryBreadcrumbCategory()?->id);
    }
}
