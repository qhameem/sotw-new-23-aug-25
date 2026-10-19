<?php

use App\Models\Category;
use App\Models\Type;
use App\Services\FactExtractorService;
use App\Services\ListingAiClient;
use App\Services\ListingGenerationService;
use App\Services\OutputValidator;
use App\Services\PlatformDetector;
use App\Services\SeoTitleBuilder;
use App\Support\ExtractionReport;

it('preserves classification evidence quotes when the semantic label is absent from source', function () {
    request()->setUserResolver(fn () => new class
    {
        public function hasRole(string $role): bool
        {
            return $role === 'admin';
        }
    });
    $category = Category::factory()->create(['name' => 'Subscription']);
    $category->types()->attach(Type::create(['name' => 'Pricing']));
    $ai = Mockery::mock(ListingAiClient::class);
    $ai->shouldReceive('prompt')->once()->andReturn('classify');
    $ai->shouldReceive('json')->once()->andReturn(['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [['name' => 'Subscription', 'quote' => '$9.99/month']], 'platforms' => [], 'suggestions' => ['categories' => [], 'use_cases' => []]]);
    $service = new ListingGenerationService($ai, new FactExtractorService($ai), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $method = new ReflectionMethod($service, 'classify');
    $result = $method->invoke($service, '{}', 'Starter $9.99/month.', []);
    expect($result['pricing'])->toBe([$category->id]);
    expect(implode("\n", array_column(ExtractionReport::current()->payload(request())['extraction_report'], 'message')))->toContain('Subscription', '$9.99/month', 'category accepted');
});
