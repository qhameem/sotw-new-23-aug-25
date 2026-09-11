<?php

use App\Services\TaglineRewriterService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::clear();
    config([
        'services.groq.key' => 'groq-key',
        'services.groq.model' => 'groq-test',
        'services.google.api_key' => 'gemini-key',
        'services.google.gemini_timeout' => 30,
        'services.openrouter.key' => 'openrouter-key',
        'services.openrouter.timeout' => 45,
        'services.ai_tagline.timeout' => 15,
        'services.ai_tagline.max_description_characters' => 1000,
        'services.ai_tagline.max_context_characters' => 2000,
        'services.ai_tagline.max_output_tokens' => 120,
        'services.ai_tagline.cache_minutes' => 1440,
    ]);
});

test('successful tagline generation is cached', function () {
    Http::fake([
        'api.groq.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '{"tagline":"Automate finance reports"}',
                ],
            ]],
        ]),
    ]);

    $service = app(TaglineRewriterService::class);
    $first = $service->rewrite('Ledgerly', 'Finance reporting', 'Context');
    $second = $service->rewrite('Ledgerly', 'Finance reporting', 'Context');

    expect($second)->toBe($first);
    Http::assertSentCount(1);
});

test('taglines use one groq request with compact context and limited output', function () {
    Http::fake([
        'api.groq.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'tagline' => 'Automate recurring finance reports',
                    ]),
                ],
            ]],
        ]),
    ]);

    $result = app(TaglineRewriterService::class)->rewrite(
        'Ledgerly',
        'Automated finance reporting',
        str_repeat('context ', 1000)
    );

    expect($result['tagline'])->toBe('Automate recurring finance reports');

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();
        $prompt = $payload['messages'][0]['content'];

        return str_contains($request->url(), 'api.groq.com')
            && $payload['max_tokens'] === 120
            && ! isset($payload['response_format'])
            && str_contains($prompt, 'Write three distinct, clear, factual tagline candidates')
            && ! str_contains($prompt, 'product_page_tagline')
            && mb_strlen($prompt) < 3000;
    });
});

test('selects an original candidate instead of copying a source heading', function () {
    Http::fake([
        'api.groq.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'candidates' => [
                            'A Teleprompter app that helps you shoot faster',
                            'Voice-following teleprompter for smoother video recording',
                            'Private teleprompter with local script storage',
                        ],
                    ]),
                ],
            ]],
        ]),
    ]);

    $result = app(TaglineRewriterService::class)->rewrite(
        'Scriptly',
        'A voice-following teleprompter with local storage',
        "Title: Scriptly\nH1: A Teleprompter app that helps you shoot faster"
    );

    expect($result['tagline'])->toBe('Voice-following teleprompter for smoother video recording');
    Http::assertSentCount(1);
});

test('retries once when every candidate copies a source heading', function () {
    Http::fakeSequence()
        ->push([
            'choices' => [[
                'message' => ['content' => '{"candidates":["A Teleprompter app that helps you shoot faster"]}'],
            ]],
        ])
        ->push([
            'choices' => [[
                'message' => ['content' => '{"candidates":["Voice-following teleprompter for smoother video recording"]}'],
            ]],
        ]);

    $result = app(TaglineRewriterService::class)->rewrite(
        'Scriptly',
        'A voice-following teleprompter with local storage',
        'H1: A Teleprompter app that helps you shoot faster'
    );

    expect($result['tagline'])->toBe('Voice-following teleprompter for smoother video recording');
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => str_contains($request->data()['messages'][0]['content'], 'Do not copy or lightly paraphrase')
        && str_contains($request->data()['messages'][0]['content'], 'A Teleprompter app that helps you shoot faster')
        && ! str_contains($request->data()['messages'][0]['content'], 'Website context:'));
});

test('originality retry generates a product-level tagline after copied feature headings', function () {
    config([
        'services.google.api_key' => null,
        'services.openrouter.key' => null,
    ]);
    Http::fakeSequence()
        ->push([
            'choices' => [[
                'message' => ['content' => '{"candidates":["A launch day you can actually watch."]}'],
            ]],
        ])
        ->push([
            'choices' => [[
                'message' => ['content' => '{"candidates":["Privacy-first analytics connecting traffic, signups, and revenue"]}'],
            ]],
        ]);

    $result = app(TaglineRewriterService::class)->rewrite(
        'Piqo Analytics',
        'Privacy-first analytics that connects visits to signups and revenue',
        "Title: Piqo Analytics — Grow your traffic, search, and revenue\nH3: A launch day you can actually watch."
    );

    expect($result['tagline'])->toBe('Privacy-first analytics connecting traffic, signups, and revenue');
    Http::assertSentCount(2);
});

test('records unusable ai candidates before falling back', function () {
    config([
        'services.google.api_key' => null,
        'services.openrouter.key' => null,
    ]);
    Http::fakeSequence()
        ->push([
            'choices' => [[
                'message' => ['content' => '{"candidates":["A launch day you can actually watch."]}'],
            ]],
        ])
        ->push([
            'choices' => [[
                'message' => ['content' => '{"candidates":["A launch day you can actually watch."]}'],
            ]],
        ]);

    $service = app(TaglineRewriterService::class);
    $result = $service->rewrite(
        'Piqo Analytics',
        'Privacy-first website analytics',
        "Title: Piqo Analytics\nH3: A launch day you can actually watch."
    );

    expect($result)->toBeNull()
        ->and($service->getFailures())->toHaveCount(1)
        ->and($service->getFailures()[0]['provider'])->toBe('groq')
        ->and($service->getFailures()[0]['body'])->toContain('no usable original tagline');
    Http::assertSentCount(2);
});

test('tagline generation fails over to the next provider', function () {
    Http::fake([
        'api.groq.com/*' => Http::response(['error' => ['message' => 'Bad request']], 400),
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => '{"tagline":"Automate recurring finance reports"}',
                    ]],
                ],
            ]],
        ]),
    ]);

    $service = app(TaglineRewriterService::class);
    $result = $service->rewrite('Ledgerly', 'Finance reporting', 'Context');

    expect($result['tagline'])->toBe('Automate recurring finance reports')
        ->and($service->getFailures())->toHaveCount(1)
        ->and($service->getFailures()[0]['provider'])->toBe('groq');
    Http::assertSentCount(2);
});

test('tagline generation tries every configured provider before fallback', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'Unavailable']], 503)]);

    $result = app(TaglineRewriterService::class)->rewrite('Ledgerly', 'Finance reporting', 'Context');

    expect($result)->toBeNull();
    Http::assertSentCount(3);
});

test('openrouter uses its provider timeout for tagline generation', function () {
    config([
        'services.groq.key' => null,
        'services.google.api_key' => null,
        'services.ai_tagline.timeout' => 15,
        'services.openrouter.timeout' => 45,
    ]);
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [[
                'message' => ['content' => '{"tagline":"Automate recurring finance reports"}'],
            ]],
        ]),
    ]);

    $service = app(TaglineRewriterService::class);
    $result = $service->rewrite('Ledgerly', 'Finance reporting', 'Context');
    $timeout = new ReflectionMethod($service, 'timeout');

    expect($result['tagline'])->toBe('Automate recurring finance reports')
        ->and($timeout->invoke($service, 'openrouter'))->toBe(45);
    Http::assertSent(fn (Request $request): bool => $request->toPsrRequest()->getUri()->getHost() === 'openrouter.ai'
        && $request['model'] === 'openrouter/free');
});
