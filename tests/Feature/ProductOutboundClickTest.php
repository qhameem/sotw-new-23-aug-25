<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOutboundClickTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_product_click_records_an_outbound_click_and_redirects_with_tracking_parameters()
    {
        $product = Product::factory()->create([
            'link' => 'https://example.com/pricing?ref=abc',
            'outbound_clicks_count' => 0,
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.click', ['product' => $product->slug, 'surface' => 'product_list']));

        $response->assertRedirect();
        $product->refresh();

        $this->assertEquals(1, $product->outbound_clicks_count);
        $this->assertEquals(1, $product->votes_count);
        $this->assertStringContainsString('ref=abc', $response->headers->get('Location'));
        $this->assertStringContainsString('utm_source=softwareontheweb.com', $response->headers->get('Location'));
        $this->assertStringContainsString('utm_medium=product_list', $response->headers->get('Location'));
    }

    /** @test */
    public function every_second_outbound_click_adds_one_vote()
    {
        $product = Product::factory()->create([
            'outbound_clicks_count' => 0,
            'votes_count' => 1,
        ]);

        $this->get(route('products.click', ['product' => $product->slug, 'surface' => 'product_details']))->assertRedirect();
        $this->get(route('products.click', ['product' => $product->slug, 'surface' => 'product_details']))->assertRedirect();

        $product->refresh();

        $this->assertEquals(2, $product->outbound_clicks_count);
        $this->assertEquals(2, $product->votes_count);
    }

    /** @test */
    public function promoted_listing_clicks_keep_their_existing_utm_medium()
    {
        $product = Product::factory()->create([
            'link' => 'https://example.com',
        ]);

        $response = $this->get(route('products.click', ['product' => $product->slug, 'surface' => 'promoted_listing_card']));

        $response->assertRedirect();
        $this->assertStringContainsString('utm_medium=promoted_listing_card', $response->headers->get('Location'));
    }
}
