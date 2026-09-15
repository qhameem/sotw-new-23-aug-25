<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductOgImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_has_product_specific_open_graph_image_metadata(): void
    {
        $product = Product::factory()->create([
            'name' => 'Metadata Product',
            'slug' => 'metadata-product',
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('property="og:image" content="'.route('products.og-image', $product), false);
        $response->assertSee('property="og:image:type" content="image/jpeg"', false);
        $response->assertSee('property="og:image:width" content="1200"', false);
        $response->assertSee('property="og:image:height" content="630"', false);
        $response->assertSee('name="twitter:image"', false);
    }

    public function test_open_graph_image_endpoint_generates_and_caches_a_1200_by_630_jpeg(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create([
            'name' => 'Generated Preview',
            'slug' => 'generated-preview',
        ]);

        $response = $this->get(route('products.og-image', $product));

        $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $dimensions = getimagesizefromstring($response->getContent());
        $this->assertSame(1200, $dimensions[0]);
        $this->assertSame(630, $dimensions[1]);
        $this->assertCount(1, Storage::disk('public')->allFiles('og_images/products'));

        $this->get(route('products.og-image', $product))->assertOk();
        $this->assertCount(1, Storage::disk('public')->allFiles('og_images/products'));
    }

    public function test_open_graph_image_is_not_exposed_for_unpublished_products(): void
    {
        $product = Product::factory()->create([
            'approved' => true,
            'is_published' => false,
        ]);

        $this->get(route('products.og-image', $product))->assertNotFound();
    }
}
