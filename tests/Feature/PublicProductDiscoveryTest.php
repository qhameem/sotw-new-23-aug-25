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
    public function week_archive_pages_render_direct_html_links_to_other_active_weeks()
    {
        $olderWeekStart = now()->copy()->subWeeks(2)->startOfWeek(Carbon::MONDAY);
        $selectedWeekStart = now()->copy()->subWeek()->startOfWeek(Carbon::MONDAY);

        Product::factory()->create([
            'slug' => 'older-week-product',
            'published_at' => $olderWeekStart->copy()->addDay(),
            'votes_count' => 1,
        ]);

        Product::factory()->create([
            'slug' => 'selected-week-product',
            'published_at' => $selectedWeekStart->copy()->addDay(),
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.byWeek', [
            'year' => $selectedWeekStart->year,
            'week' => $selectedWeekStart->weekOfYear,
        ]));

        $response->assertOk();
        $response->assertSee(route('products.byWeek', [
            'year' => $olderWeekStart->year,
            'week' => $olderWeekStart->weekOfYear,
        ]), false);
    }

    /** @test */
    public function week_archive_pages_output_a_self_referencing_canonical_tag()
    {
        $weekStart = now()->copy()->subWeek()->startOfWeek(Carbon::MONDAY);

        Product::factory()->create([
            'slug' => 'canonical-week-product',
            'published_at' => $weekStart->copy()->addDay(),
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.byWeek', [
            'year' => $weekStart->year,
            'week' => $weekStart->weekOfYear,
        ]));

        $response->assertOk();
        $response->assertSee(
            '<link rel="canonical" href="' . route('products.byWeek', [
                'year' => $weekStart->year,
                'week' => $weekStart->weekOfYear,
            ]) . '" />',
            false
        );
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
