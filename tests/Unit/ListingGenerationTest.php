<?php

use App\Services\DescriptionRewriterService;
use App\Services\FactExtractorService;
use App\Services\ListingAiClient;
use App\Services\ListingGenerationService;
use App\Services\NameExtractorService;
use App\Services\OutputValidator;
use App\Services\PlatformDetector;
use App\Services\ProductSourceCollector;
use App\Services\SeoTitleBuilder;
use App\Support\ProductDescriptionTemplates;
use Illuminate\Support\Facades\Http;

it('rejects incomplete endings and banned copy', function () {
    $validator = new OutputValidator;
    expect($validator->text('tagline', 'Weave task manager for creative teams and'))->not->toBeEmpty();
    expect($validator->text('tagline', 'A seamless task manager for creative teams'))->not->toBeEmpty();
});

it('accepts a concise supported summary without padding', function () {
    $validator = new OutputValidator;
    $summary = 'HireKey helps job seekers compare their resumes with role requirements, tailor applications, practice interviews with a voice coach, and track applications. It also offers cover letter drafting and company research tools.';
    expect($validator->text('summary', $summary))->toBe([]);
    expect($validator->text('summary', 'HireKey helps job seekers track applications.'))->toBe([]);
});

it('normalizes named feature objects in a generated description', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('prompt');
    $client->shouldReceive('json')->once()->andReturn([
        'summary' => 'Quivly helps teams record meetings, organize discussion notes, and find follow up tasks in one workspace. It provides searchable transcripts and shared notes so participants can review decisions and keep track of work after each conversation.',
        'features' => [
            ['name' => 'Meeting notes', 'description' => 'Review key decisions after a call.'],
            ['title' => 'Searchable transcripts', 'benefit' => 'Find past discussion details.'],
            ['feature' => 'Shared workspace'],
            'Task tracking: Keep follow up work visible.',
        ],
        'best_for' => 'Teams that need meeting records.',
        'not_for' => 'People seeking an offline recorder.',
        'faq' => [
            ['question' => 'What is Quivly?', 'answer' => 'A meeting notes tool.'],
            ['question' => 'Can teams share notes?', 'answer' => 'Yes, they can share notes.'],
            ['question' => 'Are transcripts searchable?', 'answer' => 'Yes, they are searchable.'],
            ['question' => 'Does it track tasks?', 'answer' => 'It tracks follow up work.'],
        ],
    ]);
    app()->instance(ListingAiClient::class, $client);
    $templates = Mockery::mock(ProductDescriptionTemplates::class);
    $templates->shouldReceive('activeInstruction')->once()->andReturn(null);
    app()->instance(ProductDescriptionTemplates::class, $templates);

    $result = app(DescriptionRewriterService::class)->generateFromFacts('Quivly', []);
    expect($result['features'])->toBe([
        'Meeting notes: Review key decisions after a call.',
        'Searchable transcripts: Find past discussion details.',
        'Shared workspace',
        'Task tracking: Keep follow up work visible.',
    ]);
});

it('builds a complete title without truncation', function () {
    $builder = new SeoTitleBuilder;
    expect($builder->build('Weave', 'Task planning for independent creative teams today'))
        ->toBe('Weave: Task planning for independent creative teams today');
    expect($builder->build('Weave', 'Task planning for independent creative teams and'))->toBeNull();
    $completeTitle = 'BetterWispr: Voice notes and dictation for professional teams';
    expect(mb_strlen($completeTitle))->toBe(61);
    expect($builder->build('BetterWispr', 'Voice notes and dictation for professional teams'))->toBe($completeTitle);
    expect($builder->build('BetterWispr', 'Voice notes and dictation for productive teams everywhere today'))->toBeNull();
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

it('accepts classification evidence across whitespace differences without accepting changed words', function () {
    $picks = [
        ['name' => 'Sports', 'quote' => ' football profiles '],
        ['name' => 'Free', 'quote' => 'Free plan'],
        ['name' => 'Subscription', 'quote' => 'Paid plan'],
    ];
    expect(ListingGenerationService::verifiedPicks($picks, "Create football\nprofiles. Free\tplan.", 3))->toBe(['Sports', 'Free']);
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

it('keeps supported facts when AI omits optional fields or evidence', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('prompt');
    $client->shouldReceive('json')->once()->andReturn([
        'product_type' => 'Desktop utility',
        'one_line_description' => 'Shows notes beside the screen notch.',
        'pricing_model' => 'Subscription',
        'evidence' => [
            'product_type' => 'Desktop utility',
            'one_line_description' => 'Shows notes beside the screen notch.',
        ],
    ]);

    $facts = (new FactExtractorService($client))->extract('Desktop utility. Shows notes beside the screen notch.');
    expect($facts['product_type'])->toBe('Desktop utility');
    expect($facts['pricing_model'])->toBeNull();
    expect($facts['platforms'])->toBe([]);
});

it('accepts evidence with equivalent whitespace', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('prompt');
    $client->shouldReceive('json')->once()->andReturn([
        'one_line_description' => 'Tailor your resume to the role.',
        'evidence' => ['one_line_description' => "Tailor your resume\nto the role."],
    ]);

    $facts = (new FactExtractorService($client))->extract('Tailor your resume to the role.');
    expect($facts['one_line_description'])->toBe('Tailor your resume to the role.');
});

it('tells the AI which evidence failed before retrying', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('prompt');
    $client->shouldReceive('json')->once()->with('prompt', 3000)->andReturn([
        'one_line_description' => 'Job search support.',
        'evidence' => ['one_line_description' => 'Unsupported paraphrase'],
    ]);
    $client->shouldReceive('json')->once()->withArgs(fn ($prompt, $tokens) => $tokens === 3000 && str_contains($prompt, 'one_line_description') && str_contains($prompt, 'Copy short evidence quotes verbatim'))->andReturn([
        'one_line_description' => 'Track every application and next step.',
        'evidence' => ['one_line_description' => 'Track every application and next step.'],
    ]);

    $facts = (new FactExtractorService($client))->extract('Track every application and next step.');
    expect($facts['one_line_description'])->toBe('Track every application and next step.');
});

it('marks failed generation as draft after two attempts', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->andReturn('prompt');
    $client->shouldReceive('json')->andReturn(null);
    $client->shouldReceive('errors')->andReturn(['AI provider returned no usable response.']);
    app()->instance(ListingAiClient::class, $client);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $result = $service->generate('Example', 'Source: Example');
    expect($result['generation_status'])->toBe('draft');
    expect($result['generation_noindex'])->toBeTrue();
    expect($result['generation_review_required'])->toBeTrue();
});

it('accepts a valid meta description when the SEO title was built in code', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('prompt');
    $meta = 'Weave helps independent creators organize tasks and project notes in one workspace. It runs in a browser and offers a free plan for individual work.';
    $client->shouldReceive('json')->once()->andReturn([
        'seo_title' => 'An invalid generated title',
        'meta_description' => $meta,
        'seo_title_length' => 0,
        'meta_description_length' => 0,
    ]);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $method = new ReflectionMethod($service, 'generateField');
    $result = $method->invoke($service, 'seo_fields_prompt.txt', [], 'seo', '', '', 2, true);
    expect($result['meta_description'])->toBe($meta);
});

it('accepts a complete short meta description without generated length fields', function () {
    $validator = new OutputValidator;
    $meta = 'Quivly records meetings and organizes notes for teams. Search transcripts, review decisions, and follow up after each call.';
    expect(mb_strlen($meta))->toBe(123);
    expect($validator->text('meta_description', $meta))->toBe([]);
    expect($validator->schema('seo', ['seo_title' => 'Quivly: Meeting notes and searchable team transcripts', 'meta_description' => $meta]))->toBe([]);
    expect($validator->text('meta_description', 'Incomplete description'))->not->toBe([]);
});

it('reports the exact eight word source overlap', function () {
    $validator = new OutputValidator;
    $source = 'Ngram helps teams create consistent product descriptions from verified website content.';
    $errors = $validator->text('meta_description', 'Create consistent product descriptions from verified website content today.', $source);

    expect($errors)->toContain('meta_description repeats eight consecutive source words: "create consistent product descriptions from verified website content".');
});

it('selects the first original meta description candidate', function () {
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('prompt');
    $copied = 'Ngram helps teams create consistent product descriptions from verified website content.';
    $original = 'Build accurate software listings from verified page details. Ngram produces concise directory copy while keeping unsupported claims out.';
    $client->shouldReceive('json')->once()->andReturn([
        'seo_title' => 'Ngram: Product descriptions from verified site facts',
        'meta_descriptions' => [$copied, $original, 'Another complete description for Ngram.'],
    ]);
    $service = new ListingGenerationService($client, new FactExtractorService($client), new PlatformDetector, new OutputValidator, new SeoTitleBuilder);
    $method = new ReflectionMethod($service, 'generateField');

    $result = $method->invoke($service, 'seo_fields_prompt.txt', [], 'seo', 'Ngram helps teams create consistent product descriptions from verified website content.', '', 1, true);

    expect($result['meta_description'])->toBe($original);
});
