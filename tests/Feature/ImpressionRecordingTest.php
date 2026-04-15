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
        $product = Product::factory()->create([
            'impressions' => 0,
            'votes_count' => 1,
        ]);

        // Act: Simulate the user viewing the product
        $this->actingAs($user)->get(route('products.show', $product));

        // Assert: Impression increments, vote stays unchanged before hitting 4 views
        $this->assertEquals(1, $product->fresh()->impressions);
        $this->assertEquals(1, $product->fresh()->votes_count);
    }

    /** @test */
    public function a_product_gets_one_automatic_vote_on_every_fourth_view()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'impressions' => 0,
            'votes_count' => 1,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->get(route('products.show', $product));
        }

        $product->refresh();
        $this->assertEquals(3, $product->impressions);
        $this->assertEquals(1, $product->votes_count);

        $this->actingAs($user)->get(route('products.show', $product));

        $product->refresh();
        $this->assertEquals(4, $product->impressions);
        $this->assertEquals(2, $product->votes_count);
    }

    /** @test */
    public function impression_api_applies_the_same_four_views_to_one_vote_rule()
    {
        $product = Product::factory()->create([
            'impressions' => 0,
            'votes_count' => 1,
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/impressions', [
                'products' => [$product->id],
            ])->assertOk();
        }

        $product->refresh();
        $this->assertEquals(4, $product->impressions);
        $this->assertEquals(2, $product->votes_count);
    }
}
