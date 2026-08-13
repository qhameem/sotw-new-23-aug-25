<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\PaidSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductDuplicateUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_check_identifies_an_existing_normalized_product_url(): void
    {
        $product = Product::factory()->create([
            'link' => 'https://outrank.so?via=sotw',
        ]);

        $this->getJson('/check-product-url?url='.urlencode('https://www.outrank.so/'))
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('product.id', $product->id);
    }

    public function test_ajax_submission_returns_a_link_validation_error_for_a_duplicate_url(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'link' => 'https://www.example.com/product/?ref=existing',
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

    public function test_paid_submission_rejects_a_legacy_formatted_duplicate_url(): void
    {
        $user = User::factory()->create();
        Product::factory()->create([
            'link' => 'https://www.outrank.so/?via=sotw',
        ]);

        $request = Request::create('/stripe/paid-submission/checkout', 'POST', [
            'name' => 'Outrank Duplicate',
            'tagline' => 'Duplicate tagline',
            'link' => 'https://outrank.so',
            'custom_categories' => [
                ['name' => 'Software', 'type' => 'category'],
            ],
        ]);

        try {
            app(PaidSubmissionService::class)->stageCheckoutFromRequest($request, $user);
            $this->fail('The duplicate paid submission was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('link', $exception->errors());
        }

        $this->assertDatabaseCount('paid_submission_checkouts', 0);
        $this->assertSame(1, Product::count());
    }
}
