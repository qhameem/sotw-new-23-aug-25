<?php

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
    foreach ([$previous->id => 5, $unpublished->id => 50] as $id => $upvotes) {
        ProductWeekStat::create(['product_id' => $id, 'week_start' => '2025-12-29', 'manual_upvotes' => $upvotes]);
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
        ->assertSee('href="'.route('products.catalog').'"', false)
        ->assertSee('aria-label="Breadcrumb"', false)
        ->assertSee('Previous week launch')
        ->assertSee('Top software categories')
        ->assertSee('aria-current="page"', false);

    expect($response->viewData('weekly'))->toHaveCount(12);
    expect($response->viewData('weekly')->last()['launches'])->toBe(1);
    expect($response->viewData('weekly')->get(10)['upvotes'])->toBe(5);
});
