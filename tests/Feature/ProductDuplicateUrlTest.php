<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDuplicateUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_check_identifies_an_existing_normalized_product_url(): void
    {
        $product = Product::factory()->create([
            'link' => 'https://www.example.com/product/',
        ]);

        $this->getJson('/check-product-url?url='.urlencode('https://www.example.com/product/?utm_source=test'))
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('product.id', $product->id);
    }

    public function test_ajax_submission_returns_a_link_validation_error_for_a_duplicate_url(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'link' => 'https://www.example.com/product/',
        ]);

        $this->actingAs($user)
            ->postJson(route('products.store'), [
                'name' => 'Duplicate Product',
                'tagline' => 'Duplicate tagline',
                'link' => 'https://www.example.com/product/?utm_campaign=duplicate',
                'custom_categories' => [
                    ['name' => 'Software', 'type' => 'category'],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('link');

        $this->assertSame(1, Product::count());
    }
}
