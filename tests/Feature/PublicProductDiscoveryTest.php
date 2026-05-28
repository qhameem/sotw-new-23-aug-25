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
        $this->assertStringContainsString('"@type": "ItemList"', $html);
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
    public function week_archive_pages_output_a_non_empty_meta_description()
    {
        $weekStart = now()->copy()->subWeek()->startOfWeek(Carbon::MONDAY);

        Product::factory()->create([
            'slug' => 'meta-week-product',
            'published_at' => $weekStart->copy()->addDay(),
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.byWeek', [
            'year' => $weekStart->year,
            'week' => $weekStart->weekOfYear,
        ]));

        $response->assertOk();
        $response->assertSee(
            'Explore the best software from Week ' . $weekStart->weekOfYear . ' of ' . $weekStart->year,
            false
        );
    }

    /** @test */
    public function date_archive_requests_permanently_redirect_to_the_matching_week_archive_url()
    {
        $date = Carbon::parse('2025-08-22');

        $response = $this->get(route('products.byDate', [
            'date' => $date->toDateString(),
        ]));

        $response->assertRedirect(route('products.byWeek', [
            'year' => $date->year,
            'week' => $date->weekOfYear,
        ]));
        $this->assertSame(301, $response->getStatusCode());
    }

    /** @test */
    public function week_archive_requests_before_the_first_published_week_return_a_404()
    {
        Product::factory()->create([
            'slug' => 'current-week-product',
            'published_at' => now()->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->addDay(),
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.byWeek', [
            'year' => 1999,
            'week' => 46,
        ]));

        $response->assertNotFound();
    }

    /** @test */
    public function week_archive_requests_with_invalid_week_numbers_return_a_404()
    {
        Product::factory()->create([
            'slug' => 'invalid-week-guard-product',
            'published_at' => now()->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->addDay(),
            'votes_count' => 1,
        ]);

        $this->get(route('products.byWeek', [
            'year' => now()->year,
            'week' => 0,
        ]))->assertNotFound();

        $this->get(route('products.byWeek', [
            'year' => now()->year,
            'week' => 54,
        ]))->assertNotFound();
    }

    /** @test */
    public function empty_week_archive_requests_redirect_to_the_last_available_week_url()
    {
        $lastAvailableWeek = now()->copy()->subWeeks(2)->startOfWeek(Carbon::MONDAY);
        $emptyWeek = now()->copy()->subWeek()->startOfWeek(Carbon::MONDAY);

        Product::factory()->create([
            'slug' => 'last-available-week-product',
            'published_at' => $lastAvailableWeek->copy()->addDay(),
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.byWeek', [
            'year' => $emptyWeek->year,
            'week' => $emptyWeek->weekOfYear,
        ]));

        $response->assertRedirect(route('products.byWeek', [
            'year' => $lastAvailableWeek->year,
            'week' => $lastAvailableWeek->weekOfYear,
        ]));
    }

    /** @test */
    public function viewing_a_product_page_does_not_increment_impressions_on_the_initial_server_response()
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

        $this->assertSame(0, $product->impressions);
        $this->assertSame(1, $product->votes_count);
        $this->assertTrue($product->updated_at->equalTo($originalUpdatedAt));
    }

    /** @test */
    public function product_pages_show_the_launch_date_and_include_date_published_structured_data()
    {
        $publishedAt = Carbon::parse('2026-05-11 04:00:00', 'UTC');
        $product = Product::factory()->create([
            'name' => 'Launch Date Product',
            'slug' => 'launch-date-product',
            'published_at' => $publishedAt,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Launched');
        $response->assertSee('May 11, 2026');
        $response->assertSee('"datePublished": "2026-05-11T04:00:00+00:00"', false);
    }

    /** @test */
    public function product_pages_use_the_short_tagline_in_the_title_without_review_wording()
    {
        $product = Product::factory()->create([
            'name' => 'SaaSOffers',
            'slug' => 'saasoffers',
            'tagline' => 'Startup Credits Platform',
            'product_page_tagline' => 'Discover and claim exclusive startup discounts for essential software.',
            'published_at' => now(),
            'votes_count' => 1,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee(
            '<title>SaaSOffers: Startup Credits Platform | Software on the Web</title>',
            false
        );
        $response->assertDontSee('Review', false);
    }
}
