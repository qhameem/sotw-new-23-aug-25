<?php

use App\Services\FactExtractorService;
use App\Services\ListingAiClient;
use App\Services\ListingGenerationService;
use App\Services\NameExtractorService;
use App\Services\OutputValidator;
use App\Services\PlatformDetector;
use App\Services\ProductSourceCollector;
use App\Services\SeoTitleBuilder;
use Illuminate\Support\Facades\Http;

it('rejects incomplete endings and banned copy', function () {
    $validator = new OutputValidator;
    expect($validator->text('tagline', 'Weave task manager for creative teams and'))->not->toBeEmpty();
    expect($validator->text('tagline', 'A seamless task manager for creative teams'))->not->toBeEmpty();
});

it('builds a complete title without truncation', function () {
    $builder = new SeoTitleBuilder;
    expect($builder->build('Weave', 'Task planning for independent creative teams today'))
        ->toBe('Weave: Task planning for independent creative teams today');
    expect($builder->build('Weave', 'Task planning for independent creative teams and'))->toBeNull();
});

it('detects NotchOwl as MacOS without assuming Browser', function () {
    $platforms = (new PlatformDetector)->detect('NotchOwl is a Mac notch utility. Download NotchOwl.dmg for your Mac. Visit our marketing website.');
    expect($platforms)->toBe(['MacOS']);
});

it('prefers og and JSON-LD names without AI', function () {
    $extractor = new NameExtractorService;
    expect($extractor->extractFromHtml('<title>Marketing copy - Wrong</title><meta property="og:site_name" content="NotchOwl">', 'https://notchowl.test'))->toBe('NotchOwl');
    expect($extractor->extractFromHtml('<title>Marketing copy - Wrong</title><script type="application/ld+json">{"@type":"SoftwareApplication","name":"Weave"}</script>', 'https://weave.test'))->toBe('Weave');
});

it('drops classification picks without source quotes', function () {
    $source = 'NotchOwl offers a Mac notch app for task notes.';
    $picks = [
        ['name' => 'Meeting Summaries', 'quote' => 'meeting summaries'],
        ['name' => 'Remote Teams', 'quote' => 'remote teams'],
        ['name' => 'Task Management', 'quote' => 'task notes'],
    ];
    expect(ListingGenerationService::verifiedPicks($picks, $source, 2))->toBe(['Task Management']);
});

it('collects body facts when the meta description is empty', function () {
    Http::fake(['*' => Http::response('', 404)]);
    $source = (new ProductSourceCollector)->collect('https://example.com', '<html><head><title>Example</title><meta name="description" content=""></head><body><main>Exports notes to PDF.</main></body></html>');
    expect($source)->toContain('Exports notes to PDF.');
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->withArgs(fn ($file, $bindings) => $file === 'facts_extraction_prompt.txt' && str_contains($bindings['{sourceText}'], 'Exports notes to PDF.'))->andReturn('prompt');
    $client->shouldReceive('json')->once()->andReturn([
        'product_name' => 'Example', 'product_type' => null, 'one_line_description' => 'Exports notes to PDF.',
        'platforms' => [], 'minimum_os' => null, 'price_amount' => null, 'currency' => null,
        'pricing_model' => null, 'free_plan' => null, 'free_trial' => null, 'license' => null,
        'target_user' => null, 'top_features' => [], 'integrations' => [], 'data_storage' => null,
        'version' => null, 'last_updated' => null, 'maker_name' => null, 'company_name' => null,
        'evidence' => ['product_name' => 'Example', 'one_line_description' => 'Exports notes to PDF.'],
    ]);
    expect((new FactExtractorService($client))->extract($source)['one_line_description'])->toBe('Exports notes to PDF.');
});

it('marks failed generation as draft after two attempts', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('prompt');
    $client->shouldReceive('json')->twice()->andReturn(null);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $result = $service->generate('Example', 'Source: Example');
    expect($result['generation_status'])->toBe('draft');
    expect($result['generation_noindex'])->toBeTrue();
    expect($result['generation_review_required'])->toBeTrue();
});
