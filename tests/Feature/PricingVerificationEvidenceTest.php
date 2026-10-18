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

it('passes semantic billing evidence to verification and reports quotes only to admins', function (bool $admin) {
    $request = request();
    $request->setUserResolver(fn () => new class($admin)
    {
        public function __construct(private bool $admin) {}

        public function hasRole(string $role): bool
        {
            return $this->admin && $role === 'admin';
        }
    });
    $category = Category::factory()->create(['name' => 'Freemium']);
    $category->types()->attach(Type::create(['name' => 'Pricing']));
    $source = "Source: https://example.com/pricing\nFree Mode. Starter $9.99/month. Paid plans offer ad-free runs.";
    $ai = Mockery::mock(ListingAiClient::class);
    $ai->shouldReceive('prompt')->once()->withArgs(function ($file, $bindings) use ($source) {
        $evidence = json_decode($bindings['{pricingEvidenceJson}'], true);
        expect($bindings['{sourceText}'])->toBe($source);
        expect($evidence['billing_quotes']['monthly_subscription'][0]['quote'])->toBe('$9.99/month');
        expect($evidence['supported_category_candidates'])->toHaveKey('Freemium');
        $prompt = strtr(file_get_contents(resource_path('prompts/'.$file)), $bindings);
        expect($prompt)->toContain('category labels need not appear verbatim', 'Free access and paid subscriptions can coexist', '$9.99/month');

        return true;
    })->andReturn('verify');
    $ai->shouldReceive('json')->once()->andReturn(['issues' => [['field' => 'pricing', 'problem' => 'Example verifier verdict for diagnosis.', 'source_quote' => 'Free Mode.']]]);
    $service = new ListingGenerationService($ai, new FactExtractorService($ai), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $method = new ReflectionMethod($service, 'verify');
    $result = $method->invoke($service, ['pricing' => [$category->id]], '{}', $source);
    expect($result[0]['source_quote'])->toBe('Free Mode.');
    $payload = ExtractionReport::current()->payload($request);
    if ($admin) {
        $messages = implode("\n", array_column($payload['extraction_report'], 'message'));
        expect($messages)->toContain('$9.99/month', 'https://example.com/pricing', 'Free Mode.', 'verifier quote:');
    } else {
        expect($payload)->toBe([]);
    }
})->with([true, false]);

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
