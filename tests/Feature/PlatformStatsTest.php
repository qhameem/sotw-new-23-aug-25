<?php

use App\Models\AiCrawlerDailyStat;
use App\Models\Product;
use App\Models\ProductWeekStat;
use App\Models\StartupDirectory;
use Illuminate\Support\Carbon;

test('public stats count published launches and reuse homepage navigation across an ISO year boundary', function () {
    $this->travelTo(Carbon::parse('2026-01-07 12:00:00'));

    $previous = Product::factory()->create([
        'name' => 'Previous week launch',
        'published_at' => '2025-12-31 12:00:00',
        'is_promoted' => false,
    ]);
    Product::factory()->create(['published_at' => null, 'created_at' => now()->subDay()]);
    $unpublished = Product::factory()->create(['is_published' => false, 'published_at' => now()]);
    foreach ([$previous->id => 5, $unpublished->id => 50] as $id => $clicks) {
        ProductWeekStat::create(['product_id' => $id, 'week_start' => '2025-12-29', 'outbound_clicks' => $clicks]);
    }
    foreach (['2025-12-28' => 20, '2025-12-29' => 3, '2026-01-04' => 4, '2026-01-05' => 8] as $date => $requests) {
        AiCrawlerDailyStat::create([
            'visited_on' => $date,
            'bot' => 'GPTBot',
            'path_hash' => hash('sha256', '/'),
            'path' => '/',
            'status_code' => 200,
            'requests' => $requests,
            'last_seen_at' => $date.' 12:00:00',
        ]);
    }
    StartupDirectory::create([
        'name' => 'Test directory',
        'website_url' => 'https://example.com',
        'pricing' => 'free',
        'link_type' => 'dofollow',
        'dr' => 40,
    ]);

    $response = $this->get(route('stats'))
        ->assertOk()
        ->assertSee('Platform stats')
        ->assertSee('<option value="clicks">Website clicks</option>', false)
        ->assertSee('<option value="ai_requests">AI requests</option>', false)
        ->assertDontSee('value="upvotes"', false)
        ->assertSee('href="'.route('products.catalog').'"', false)
        ->assertSee('aria-label="Breadcrumb"', false)
        ->assertSee('Previous week launch')
        ->assertSee('Top software categories')
        ->assertSee('aria-current="page"', false);

    expect($response->viewData('weekly'))->toHaveCount(12);
    expect($response->viewData('weekly')->last()['launches'])->toBe(1);
    expect($response->viewData('weekly')->get(10)['clicks'])->toBe(5);
    expect($response->viewData('weekly')->get(10)['ai_requests'])->toBe(7);
    expect($response->viewData('weekly')->last()['ai_requests'])->toBe(8);
});
