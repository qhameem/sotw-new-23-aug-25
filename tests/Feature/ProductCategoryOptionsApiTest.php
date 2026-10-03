<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Type;
use App\Support\CategoryTypeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryOptionsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function categories_api_includes_use_case_options()
    {
        $useCaseType = Type::create([
            'name' => CategoryTypeRegistry::primaryNameFor(CategoryTypeRegistry::USE_CASE),
            'description' => 'Use case taxonomy',
        ]);

        $useCase = Category::create([
            'name' => 'Video Captions',
            'slug' => 'video-captions',
            'description' => 'Create captions for videos.',
            'meta_description' => 'Tools for video captions.',
        ]);

        $useCase->types()->attach($useCaseType->id);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Video Captions',
        ]);
        $response->assertJsonPath('useCases.0.name', 'Video Captions');
    }
    public function test_pricing_options_are_ordered_by_product_usage(): void
    {
        $type = Type::create(['name' => 'Pricing']);
        $rare = Category::factory()->create(['name' => 'Custom']);
        $popular = Category::factory()->create(['name' => 'Subscription']);
        $unused = Category::factory()->create(['name' => 'Free']);
        foreach ([$rare, $popular, $unused] as $category) {
            $category->types()->attach($type);
        }
        $products = \App\Models\Product::factory()->count(3)->create();
        $popular->products()->attach($products->pluck('id'));
        $rare->products()->attach($products->first()->id);

        $this->getJson('/api/categories')->assertOk()
            ->assertJsonPath('pricing.0.id', $popular->id)
            ->assertJsonPath('pricing.1.id', $rare->id)
            ->assertJsonPath('pricing.2.id', $unused->id);
    }

}
