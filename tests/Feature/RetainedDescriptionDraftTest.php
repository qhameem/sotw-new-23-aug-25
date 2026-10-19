<?php

use App\Http\Controllers\Api\DescriptionController;
use App\Models\Product;
use App\Models\User;
use App\Services\AiProviderRoutingService;
use App\Services\DescriptionRewriterService;
use App\Services\FactExtractorService;
use App\Services\ProductRegenerationLimiter;
use App\Services\ProductSourceCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('returns retained content and draft warnings from the description regeneration endpoint', function () {
    Http::fake(['*' => Http::response('<h1>Example</h1>')]);
    $collector = Mockery::mock(ProductSourceCollector::class);
    $collector->shouldReceive('collect')->once()->andReturn('Source text.');
    app()->instance(ProductSourceCollector::class, $collector);
    $facts = Mockery::mock(FactExtractorService::class);
    $facts->shouldReceive('extract')->once()->andReturn([]);
    app()->instance(FactExtractorService::class, $facts);
    $rewriter = Mockery::mock(DescriptionRewriterService::class);
    $rewriter->shouldReceive('generateFromFacts')->once()->andReturn([
        'summary' => 'Example provides desktop widgets.', 'features' => [], 'best_for' => '', 'not_for' => '', 'faq' => [],
        '_generation_warnings' => ['Copied wording needs review.'],
    ]);
    $rewriter->shouldReceive('listingProvider')->andReturn(null);
    $limiter = Mockery::mock(ProductRegenerationLimiter::class);
    $limiter->shouldReceive('consume')->once()->andReturn(['remaining' => 2]);
    $request = Request::create('/api/generate-description', 'POST', [
        'url' => 'https://8.8.8.8', 'name' => 'Example', 'draft_uuid' => '12345678-1234-1234-1234-123456789012',
    ]);
    $request->setUserResolver(fn () => User::factory()->make());
    $response = (new DescriptionController)->generate($request, $rewriter, $limiter, app(AiProviderRoutingService::class));
    $data = $response->getData(true);
    expect($response->status())->toBe(200);
    expect($data['description'])->toContain('Example provides desktop widgets.');
    expect($data['generation_status'])->toBe('draft');
    expect($data['description_notice'])->toContain('Copied wording needs review.');
    $cached = Cache::get('product_listing:'.hash('sha256', Product::normalizeLink('https://8.8.8.8')));
    expect($cached['generation_status'])->toBe('draft');
    expect($cached['generation_review_required'])->toBeTrue();
});
