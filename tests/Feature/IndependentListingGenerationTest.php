<?php

use App\Services\DescriptionRewriterService;
use App\Services\FactExtractorService;
use App\Services\ListingAiClient;
use App\Services\ListingGenerationService;
use App\Services\OutputValidator;
use App\Services\PlatformDetector;
use App\Services\SeoTitleBuilder;
use App\Services\TaglineRewriterService;
use App\Support\ListingDescription;

it('runs every listing stage and retains successful fields when another stage fails', function (string $failed, bool $throws) {
    $visited = [];
    $failure = function (string $stage, mixed $value) use ($failed, $throws, &$visited) {
        $visited[] = $stage;
        if ($stage !== $failed && $failed !== 'all') {
            return $value;
        }
        if ($throws) {
            throw new RuntimeException('Simulated failure');
        }

        return null;
    };
    $facts = ['product_name' => 'Example', 'platforms' => [], 'top_features' => [], 'integrations' => [], 'evidence' => []];
    $body = [
        'summary' => 'Example verifies human visitors.', 'features' => [], 'best_for' => '', 'not_for' => '', 'faq' => [],
        'pros' => array_map(fn ($i) => 'Supported advantage number '.$i, range(1, 12)),
    ];
    $factService = Mockery::mock(FactExtractorService::class);
    $factService->shouldReceive('extract')->once()->andReturnUsing(fn () => $failure('facts', $facts));
    $factService->shouldReceive('errors')->andReturn(['No facts available.']);
    $platforms = Mockery::mock(PlatformDetector::class);
    $platforms->shouldReceive('detect')->once()->andReturnUsing(function () use (&$visited, $failed) {
        $visited[] = 'platform';
        if ($failed === 'platform' || $failed === 'all') {
            throw new RuntimeException('Platform failed');
        }

        return [];
    });
    $tagline = Mockery::mock(TaglineRewriterService::class);
    $tagline->shouldReceive('rewriteFromFacts')->once()->andReturnUsing(fn () => $failure('tagline', 'Human verification API confirms real people online'));
    $tagline->shouldReceive('listingErrors')->andReturn(['Tagline unavailable.']);
    $tagline->shouldReceive('listingProvider')->andReturn(null);
    app()->instance(TaglineRewriterService::class, $tagline);
    $description = Mockery::mock(DescriptionRewriterService::class);
    $description->shouldReceive('generateFromFacts')->once()->andReturnUsing(fn () => $failure('description', $body));
    $description->shouldReceive('listingErrors')->andReturn(['Description unavailable.']);
    $description->shouldReceive('listingProvider')->andReturn(null);
    app()->instance(DescriptionRewriterService::class, $description);
    $ai = Mockery::mock(ListingAiClient::class);
    $ai->shouldReceive('prompt')->andReturnUsing(fn ($file) => $file);
    $ai->shouldReceive('json')->andReturnUsing(function ($prompt) use ($failure) {
        return match (true) {
            str_starts_with($prompt, 'seo_fields') => $failure('SEO', ['seo_title' => 'Example: Human verification API confirms real people online', 'meta_description' => 'Example checks human visitors through an API.']),
            str_starts_with($prompt, 'category_classification') => $failure('classification', ['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [], 'platforms' => [], 'suggestions' => ['categories' => [], 'use_cases' => []]]),
            str_starts_with($prompt, 'listing_verification') => $failure('verification', ['issues' => []]),
        };
    });
    $ai->shouldReceive('errors')->andReturn(['AI unavailable.']);
    $service = new ListingGenerationService($ai, $factService, $platforms, new OutputValidator, new SeoTitleBuilder);
    $result = $service->generate('Example', 'Source: https://example.com Human verification API.');

    foreach (['facts', 'platform', 'tagline', 'description', 'SEO', 'classification', 'verification'] as $stage) {
        expect($visited)->toContain($stage);
    }
    expect($result['generation_status'])->toBe('draft');
    expect($result['generation_errors'])->not->toBeEmpty();
    if ($failed !== 'description' && $failed !== 'all') {
        expect($result['pros'])->toHaveCount(12);
        expect($result['description'])->toContain('Supported advantage number 12');
    }
    if ($failed !== 'tagline' && $failed !== 'all') {
        expect($result['tagline'])->toBe('Human verification API confirms real people online');
    }
    if ($failed !== 'SEO' && $failed !== 'all') {
        expect($result['meta_description'])->toBe('Example checks human visitors through an API.');
    }
    if ($failed !== 'classification' && $failed !== 'all') {
        expect($result)->toHaveKey('categories');
        expect($result['field_errors']['categories'])->not->toContain('Classification failed');
    }
})->with(['facts', 'platform', 'tagline', 'description', 'SEO', 'classification', 'verification', 'all'])->with([false, true]);

it('accepts and renders every returned pro without removing content checks', function () {
    $body = ['summary' => 'Example checks visitors.', 'features' => [], 'best_for' => '', 'not_for' => '', 'faq' => [], 'pros' => array_map(fn ($i) => 'Benefit '.$i, range(1, 100))];
    expect((new OutputValidator)->schema('description', $body))->toBe([]);
    expect(ListingDescription::fields($body)['pros'])->toHaveCount(100);
    expect(ListingDescription::html($body))->toContain('<li>Benefit 100</li>');
    $body['pros'][] = ['invalid nested value'];
    expect((new OutputValidator)->schema('description', $body))->toContain('description.pros must contain strings.');
});

it('still extracts listing and technologies when homepage and logo extraction fail', function () {
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('', 503)]);
    \App\Models\TechStack::create(['name' => 'Laravel', 'slug' => 'laravel']);
    $listing = Mockery::mock(ListingGenerationService::class);
    $listing->shouldReceive('generate')->once()->andReturn(['generation_status' => 'draft', 'tagline' => 'Retained generated tagline']);
    app()->instance(ListingGenerationService::class, $listing);
    $logos = Mockery::mock(\App\Services\LogoExtractorService::class);
    $logos->shouldReceive('extract')->once()->andThrow(new RuntimeException('Logo failed'));
    app()->instance(\App\Services\LogoExtractorService::class, $logos);
    $technologies = Mockery::mock(\App\Services\TechStackDetectorService::class);
    $technologies->shouldReceive('detect')->once()->andReturn(['Laravel']);
    app()->instance(\App\Services\TechStackDetectorService::class, $technologies);
    $request = \Illuminate\Http\Request::create('/api/process-url', 'POST', ['url' => 'https://8.8.8.8', 'name' => 'Example']);
    $response = app(\App\Http\Controllers\ProductController::class)->processUrl($request);
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($data['tagline'])->toBe('Retained generated tagline');
    expect($data['logos'])->toBe([]);
    expect($data['tech_stacks'])->toHaveCount(1);
});

it('still returns metadata when initial logo and screenshot extraction throw', function () {
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('<html><head><meta property="og:site_name" content="Example"></head><body><h1>Check visitors</h1></body></html>')]);
    $logos = Mockery::mock(\App\Services\ProductLogoResolver::class);
    $logos->shouldReceive('discoverReplacementLogoUrl')->once()->andThrow(new RuntimeException('Logo failed'));
    app()->instance(\App\Services\ProductLogoResolver::class, $logos);
    $screenshots = Mockery::mock(\App\Services\ScreenshotService::class);
    $screenshots->shouldReceive('capture')->once()->andThrow(new RuntimeException('Screenshot failed'));
    app()->instance(\App\Services\ScreenshotService::class, $screenshots);
    $request = \Illuminate\Http\Request::create('/api/fetch-initial-metadata', 'POST', ['url' => 'https://8.8.8.8']);
    $response = app(\App\Http\Controllers\ProductController::class)->fetchInitialMetadata($request);
    $data = $response->getData(true);

    expect($response->getStatusCode())->toBe(200);
    expect($data['name'])->toBe('Example');
    expect($data['favicon'])->toBeNull();
    expect($data['screenshot_url'])->toBeNull();
});
