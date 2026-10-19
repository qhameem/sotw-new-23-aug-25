<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Services\DescriptionRewriterService;
use App\Services\FactExtractorService;
use App\Services\ListingAiClient;
use App\Services\ListingGenerationService;
use App\Services\OutputValidator;
use App\Services\PlatformDetector;
use App\Services\SeoTitleBuilder;
use App\Services\TaglineRewriterService;
use App\Support\ListingDescription;

function expandedListingBody(): array
{
    return [
        'summary' => 'Example helps players maintain a football profile and record their career progress.',
        'features' => ['Career records: Organize your playing history.'],
        'best_for' => 'Football players building a profile.', 'not_for' => '',
        'top_use_cases' => ['Present career achievements to clubs.'],
        'comparisons' => ['Provides football profile fields compared with a general page builder.'],
        'integrations' => ['Import career records through its API.'],
        'pros' => ['Keeps match records in one profile.'],
        'editorial_qa' => [['question' => 'How are records organized?', 'answer' => 'Each season has a separate record.']],
        'faq' => [],
    ];
}

it('maps expanded generation to the editor and persists every section without repeated features', function () {
    $body = expandedListingBody();
    $taglines = Mockery::mock(TaglineRewriterService::class);
    $taglines->shouldReceive('rewriteFromFacts')->andReturn('Football profiles for players tracking career progress');
    $taglines->shouldReceive('listingProvider')->andReturn(null);
    app()->instance(TaglineRewriterService::class, $taglines);
    $descriptions = Mockery::mock(DescriptionRewriterService::class);
    $descriptions->shouldReceive('generateFromFacts')->andReturn($body);
    $descriptions->shouldReceive('listingProvider')->andReturn(null);
    app()->instance(DescriptionRewriterService::class, $descriptions);
    $client = Mockery::mock(ListingAiClient::class);
    $requestedPrompts = [];
    $client->shouldReceive('prompt')->andReturnUsing(function ($file) use (&$requestedPrompts) {
        $requestedPrompts[] = $file;

        return $file;
    });
    $client->shouldReceive('json')->with('seo_fields_prompt.txt')->andReturn(['seo_title' => 'Example: Football profiles for career progress', 'meta_description' => 'Example helps players organize their football career.']);
    $client->shouldReceive('json')->with('category_classification_prompt.txt')->andReturn(['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [], 'platforms' => [], 'suggestions' => ['categories' => [], 'use_cases' => []]]);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $generated = $service->generate('Example', 'Football career profiles.', ['platforms' => []]);
    expect($generated['description'])->toBe(ListingDescription::html($body));
    expect($generated['generation_status'])->toBe('ready');
    expect($requestedPrompts)->toBe(['seo_fields_prompt.txt', 'category_classification_prompt.txt']);
    $stored = array_intersect_key($generated, array_flip(['summary', 'features', 'best_for_text', 'not_for_text', 'faq', 'description']));
    $product = Product::factory()->create($stored);
    expect($product->fresh()->description)->toContain($generated['description']);
    $response = $this->get(route('products.show', $product->slug))->assertOk();
    foreach (['Top Use Cases', 'Comparisons', 'Integrations', 'Pros', 'How are records organized?', 'Each season has a separate record.'] as $text) {
        $response->assertSee($text);
    }
    expect(substr_count($response->getContent(), '<li>Career records: Organize your playing history.</li>'))->toBe(1);
    $response->assertDontSee('Show more');
});

it('explains empty classification results and clears diagnostics for supported matches', function () {
    foreach (['Software Categories' => 'Sports', 'Use Cases' => 'Career Profiles', 'Pricing' => 'Free'] as $type => $name) {
        $category = Category::factory()->create(['name' => $name]);
        $category->types()->attach(Type::create(['name' => $type]));
    }
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->andReturn('classify');
    $client->shouldReceive('json')->andReturn([
        'categories' => [['name' => 'Sports', 'quote' => 'Invented claim']],
        'use_cases' => [['name' => 'Unknown option', 'quote' => 'football profiles']],
        'pricing' => [], 'best_for' => [], 'platforms' => [],
        'suggestions' => ['categories' => [], 'use_cases' => []],
    ], [
        'categories' => [['name' => 'Sports', 'quote' => 'football profiles']],
        'use_cases' => [['name' => 'Career Profiles', 'quote' => 'football profiles']],
        'pricing' => [['name' => 'Free', 'quote' => 'Free plan']], 'best_for' => [], 'platforms' => [],
        'suggestions' => ['categories' => [], 'use_cases' => []],
    ]);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $method = new ReflectionMethod($service, 'classify');
    $result = $method->invoke($service, '{}', 'Create football profiles. Free plan.', []);
    expect($result['field_errors']['categories'])->toBe('');
    expect($result['categories'])->toHaveCount(1);
    expect($result['field_errors']['useCases'])->toContain('configured options');
    expect($result['field_errors']['pricing'])->toContain('No options were returned');
    $result = $method->invoke($service, '{}', 'Create football profiles. Free plan.', []);
    expect($result['field_errors'])->toBe(['categories' => '', 'useCases' => '', 'pricing' => '']);
    expect($result['categories'])->toHaveCount(1);
});

it('validates expanded output shapes and escapes generated HTML', function () {
    $body = expandedListingBody();
    $validator = new OutputValidator;
    expect($validator->schema('description', $body))->toBe([]);
    $body['integrations'] = [['name' => 'Invalid nested value']];
    $body['editorial_qa'] = [['question' => 'Missing answer']];
    expect($validator->schema('description', $body))->not->toBeEmpty();
    $body = expandedListingBody();
    $body['pros'] = ['<script>alert(1)</script>'];
    expect(ListingDescription::html($body))->not->toContain('<script>');
});

it('regenerates an expanded description and updates the submission cache', function () {
    $body = expandedListingBody();
    $user = \App\Models\User::factory()->create();
    $url = 'https://8.8.8.8';
    \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response('<html><body>Football profiles.</body></html>')]);
    $this->mock(\App\Services\ProductRegenerationLimiter::class, fn ($mock) => $mock->shouldReceive('consume')->once()->andReturn([]));
    $this->mock(\App\Services\ProductSourceCollector::class, fn ($mock) => $mock->shouldReceive('collect')->once()->andReturn('Football profiles.'));
    $this->mock(FactExtractorService::class, fn ($mock) => $mock->shouldReceive('extract')->once()->andReturn(['platforms' => []]));
    $this->mock(DescriptionRewriterService::class, function ($mock) use ($body) {
        $mock->shouldReceive('generateFromFacts')->once()->with('Example', ['platforms' => []], 'Football profiles.', '', null)->andReturn($body);
        $mock->shouldReceive('listingProvider')->once()->andReturn(null);
        $mock->shouldNotReceive('rewrite');
    });
    $key = 'product_listing:'.hash('sha256', Product::normalizeLink($url));
    \Illuminate\Support\Facades\Cache::put($key, ['categories' => [123]], now()->addHour());
    $this->actingAs($user)->postJson('/api/generate-description', [
        'name' => 'Example', 'url' => $url, 'draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
    ])->assertOk()->assertJsonPath('description', ListingDescription::html($body));
    $cached = \Illuminate\Support\Facades\Cache::get($key);
    expect($cached['description'])->toBe(ListingDescription::html($body));
    expect($cached['categories'])->toBe([123]);
    expect($cached['features'])->toBe($body['features']);
});

it('keeps inferred browser classifications and does not cap classifications', function () {
    $ids = [];
    foreach (['One', 'Two', 'Three', 'Four'] as $name) {
        $category = Category::factory()->create(['name' => $name]);
        $category->types()->attach(Type::firstOrCreate(['name' => 'Software Categories']));
        $ids[] = $category->id;
    }
    $browser = Category::factory()->create(['name' => 'Browser']);
    $browser->types()->syncWithoutDetaching([Type::firstOrCreate(['name' => 'Platform'])->id]);
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('classify');
    $client->shouldReceive('json')->once()->andReturn([
        'categories' => array_map(fn ($name) => ['name' => $name], ['One', 'Two', 'Three', 'Four']),
        'use_cases' => [], 'best_for' => [], 'pricing' => [],
        'platforms' => [['name' => 'Browser', 'quote' => '']],
        'suggestions' => ['categories' => [], 'use_cases' => []],
    ]);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $result = (new ReflectionMethod($service, 'classify'))->invoke($service, '{}', 'Product overview.', []);
    expect($result['categories'])->toHaveCount(4);
    expect($result['platforms'])->toBe([$browser->id]);
});

it('keeps retained descriptions editable and marks the listing as draft', function () {
    $body = expandedListingBody();
    $body['_generation_warnings'] = ['faq repeats eight consecutive source words.'];
    $tagline = Mockery::mock(TaglineRewriterService::class);
    $tagline->shouldReceive('rewriteFromFacts')->once()->andReturn('Football profiles for players tracking career progress');
    $tagline->shouldReceive('listingProvider')->andReturn(null);
    app()->instance(TaglineRewriterService::class, $tagline);
    $description = Mockery::mock(DescriptionRewriterService::class);
    $description->shouldReceive('generateFromFacts')->once()->andReturn($body);
    $description->shouldReceive('listingProvider')->andReturn(null);
    app()->instance(DescriptionRewriterService::class, $description);
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->andReturnUsing(fn ($file) => $file);
    $client->shouldReceive('json')->with('seo_fields_prompt.txt')->andReturn(['seo_title' => 'Example: Football profiles for career progress', 'meta_description' => 'Example helps players organize their football career.']);
    $client->shouldReceive('json')->with('category_classification_prompt.txt')->andReturn(['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [], 'platforms' => [], 'suggestions' => ['categories' => [], 'use_cases' => []]]);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $result = $service->generate('Example', 'Football career profiles.', ['platforms' => []]);
    expect($result['generation_status'])->toBe('draft');
    expect($result['generation_review_required'])->toBeTrue();
    expect($result['description'])->toBe(ListingDescription::html($body));
    expect($result['summary'])->toBe($body['summary']);
    expect($result['description_notice'])->toContain('draft retained', 'eight consecutive');
    expect($result['field_errors']['description'])->toContain('eight consecutive');
});
