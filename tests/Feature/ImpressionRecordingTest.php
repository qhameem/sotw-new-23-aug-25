<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpressionRecordingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_impression_is_recorded_when_a_product_is_viewed()
    {
        // Arrange: Create a user and a product
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Act: Simulate the user viewing the product
        $this->actingAs($user)->get(route('products.show', $product));

        // Assert: Check that the impression count on the product was incremented
        $this->assertEquals(1, $product->fresh()->impressions);
    }
}