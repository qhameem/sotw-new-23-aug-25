<?php

use App\Models\ProductDiscoverySource;
use App\Models\ProductRecommendation;
use App\Services\ProductDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('extracts and deduplicates recommendations from a feed', function () {
    Http::fake(['8.8.8.8/*' => Http::response(<<<'XML'
        <?xml version="1.0"?>
        <rss><channel>
          <item><title>Launch: Useful SaaS</title><link>https://useful.test</link><description>A useful new platform.</description></item>
          <item><title>Launch: Useful SaaS</title><link>https://useful.test</link><description>A useful new platform.</description></item>
        </channel></rss>
        XML, 200, ['Content-Type' => 'application/rss+xml'])]);

    $source = ProductDiscoverySource::create([
        'name' => 'Test feed', 'url' => 'https://8.8.8.8/feed', 'type' => 'auto',
        'is_active' => true, 'max_items' => 30,
    ]);

    app(ProductDiscoveryService::class)->scan($source);
    app(ProductDiscoveryService::class)->scan($source);

    expect(ProductRecommendation::count())->toBe(1)
        ->and(ProductRecommendation::first()->title)->toBe('Launch: Useful SaaS')
        ->and($source->fresh()->last_success_at)->not->toBeNull();
});

it('extracts configured HTML items and resolves relative links', function () {
    Http::fake(['8.8.4.4/*' => Http::response(<<<'HTML'
        <html><main><article class="launch"><h2>Example Tool</h2><a href="/products/example">Open</a><p class="tagline">Simple productivity app</p></article></main></html>
        HTML)]);

    $source = ProductDiscoverySource::create([
        'name' => 'Test HTML', 'url' => 'https://8.8.4.4/launches', 'type' => 'html',
        'item_selector' => '.launch', 'link_selector' => 'a', 'title_selector' => 'h2',
        'description_selector' => '.tagline', 'is_active' => true, 'max_items' => 30,
    ]);

    app(ProductDiscoveryService::class)->scan($source);

    $recommendation = ProductRecommendation::first();
    expect($recommendation->title)->toBe('Example Tool')
        ->and($recommendation->url)->toBe('https://8.8.4.4/products/example')
        ->and($recommendation->description)->toBe('Simple productivity app');
});

it('inspects a page and discovers its feed and name', function () {
    Http::fake(['8.8.4.4/*' => Http::response(<<<'HTML'
        <html><head><title>Launch Board | New products</title><link rel="alternate" type="application/rss+xml" href="feeds/new.xml"></head><body></body></html>
        HTML, 200, ['Content-Type' => 'text/html'])]);

    $details = app(ProductDiscoveryService::class)->inspect('https://8.8.4.4/launches/');

    expect($details['name'])->toBe('Launch Board')
        ->and($details['type'])->toBe('feed')
        ->and($details['url'])->toBe('https://8.8.4.4/launches/feeds/new.xml');
});
