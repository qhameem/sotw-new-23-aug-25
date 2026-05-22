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
}
