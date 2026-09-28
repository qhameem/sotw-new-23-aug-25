<?php

use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-16 12:00:00'));
    Cache::flush();
});

afterEach(function () {
    Cache::flush();
    Carbon::setTestNow();
});

test('home exposes application and database timing', function () {
    Product::factory()->create(['published_at' => now()->subDay()]);

    $response = $this->get(route('home'));

    $response->assertOk();
    expect($response->headers->get('Server-Timing'))
        ->toMatch('/^app;dur=\d+\.\d, db;dur=\d+\.\d;desc="\d+ queries"$/');
});

test('home selects the latest product week and invalidates that cache for new products', function () {
    $older = Product::factory()->create([
        'name' => 'Older launch',
        'published_at' => now()->subWeeks(2),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($older->name);

    $newer = Product::factory()->create([
        'name' => 'Newest launch',
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($newer->name)
        ->assertDontSee($older->name);
});

test('effective publication range uses created time when publication time is absent', function () {
    $fallbackProduct = Product::factory()->create([
        'published_at' => null,
        'created_at' => now()->subDay(),
    ]);
    Product::factory()->create([
        'published_at' => null,
        'created_at' => now()->subMonths(2),
    ]);

    $products = Product::query()
        ->effectivePublishedBetween(now()->startOfWeek(), now()->endOfWeek())
        ->get();

    expect($products->modelKeys())->toBe([$fallbackProduct->id]);
});
