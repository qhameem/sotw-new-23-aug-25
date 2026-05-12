<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManualUpvoteWindowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function products_older_than_two_weeks_can_still_receive_manual_upvotes()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'published_at' => now()->subDays(15),
            'votes_count' => 1,
        ]);

        $response = $this->actingAs($user)->postJson(route('api.products.upvote.store', $product->slug));

        $response->assertCreated()
            ->assertJson([
                'message' => 'Product upvoted successfully.',
                'votes_count' => 2,
            ]);

        $this->assertSame(2, $product->fresh()->votes_count);
        $this->assertDatabaseHas('user_product_upvotes', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }
}
