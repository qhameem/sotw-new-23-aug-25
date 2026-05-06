<?php

namespace Tests\Feature;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_homepage_contains_a_direct_internal_link_to_each_product_detail_page()
    {
        $product = Product::factory()->create([
            'name' => 'Indexable Product',
            'slug' => 'indexable-product',
            'published_at' => now(),
            'votes_count' => 1,
            'impressions' => 0,
        ]);

        $html = view('partials.products_list', [
            'regularProducts' => collect([$product]),
            'promotedProducts' => collect(),
        ])->render();

        $this->assertStringContainsString(route('products.show', $product->slug), $html);
    }

    /** @test */
    public function viewing_a_product_page_records_an_impression_without_changing_the_editorial_last_modified_timestamp()
    {
        $originalUpdatedAt = Carbon::parse('2026-01-20 09:30:00');
        $product = Product::factory()->create([
            'slug' => 'stable-lastmod-product',
            'updated_at' => $originalUpdatedAt,
            'impressions' => 0,
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $product->refresh();

        $this->assertSame(1, $product->impressions);
        $this->assertSame(1, $product->votes_count);
        $this->assertTrue($product->updated_at->equalTo($originalUpdatedAt));
    }
}
