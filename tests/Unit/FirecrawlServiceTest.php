<?php

use App\Services\FirecrawlService;
use App\Services\ProductSourceCollector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.firecrawl.enabled' => true, 'services.firecrawl.api_key' => 'test-key']);
    Cache::flush();
});

function firecrawlTestResponse(): array
{
    return ['success' => true, 'data' => [
        'markdown' => str_repeat('Product supports scheduled exports and team collaboration. ', 10),
        'html' => '<html><title>Product</title><body>Rendered product</body></html>',
        'metadata' => ['statusCode' => 200],
    ]];
}

it('caches successful scrapes with provenance and authenticates requests', function () {
    Http::fake(['api.firecrawl.dev/*' => Http::response(firecrawlTestResponse())]);
    $service = app(FirecrawlService::class);
    $first = $service->scrape('https://8.8.8.8');
    expect($first['url'])->toBe('https://8.8.8.8');
    expect($first['retrieved_at'])->not->toBeEmpty();
    expect($service->scrape('https://8.8.8.8'))->toBe($first);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['formats'] === ['markdown', 'html']);
});

it('does not call the provider when disabled or given a private URL', function () {
    Http::fake();
    expect(app(FirecrawlService::class)->scrape('http://127.0.0.1'))->toBeNull();
    config(['services.firecrawl.enabled' => false]);
    expect(app(FirecrawlService::class)->scrape('https://8.8.8.8'))->toBeNull();
    Http::assertNothingSent();
});

it('rejects failed page outcomes and negatively caches them', function () {
    $data = firecrawlTestResponse();
    $data['data']['metadata']['statusCode'] = 403;
    Http::fake(['api.firecrawl.dev/*' => Http::response($data)]);
    expect(app(FirecrawlService::class)->scrape('https://8.8.8.8'))->toBeNull();
    expect(app(FirecrawlService::class)->scrape('https://8.8.8.8'))->toBeNull();
    Http::assertSentCount(1);
});

it('falls back for sparse pages while capping provider requests at two', function () {
    Http::fake([
        'api.firecrawl.dev/*' => Http::response(firecrawlTestResponse()),
        '*' => Http::response('<h1>Shell</h1>', 200, ['Content-Type' => 'text/html']),
    ]);
    $source = app(ProductSourceCollector::class)->collect('https://8.8.8.8', '<html></html>');
    expect($source)->toContain('Source: https://8.8.8.8', 'scheduled exports');
    expect(Http::recorded(fn ($request) => str_contains($request->url(), 'api.firecrawl.dev'))->count())->toBe(2);
});

it('keeps ordinary extraction free of provider requests when content is sufficient', function () {
    Http::fake(['*' => Http::response('', 404)]);
    app(ProductSourceCollector::class)->collect('https://8.8.8.8', '<p>'.str_repeat('Supported product details. ', 30).'</p>');
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'api.firecrawl.dev'));
});

it('preserves ordinary content when the provider fails', function () {
    Http::fake(['api.firecrawl.dev/*' => Http::response([], 429), '*' => Http::response('', 404)]);
    $source = app(ProductSourceCollector::class)->collect('https://8.8.8.8', '<h1>Existing product</h1>');
    expect($source)->toContain('Existing product');
});
