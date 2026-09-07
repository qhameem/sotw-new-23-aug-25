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
        'services.openrouter.key' => 'openrouter-key',
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
                    'content' => '{"tagline":"Automate finance reports","product_page_tagline":"Automate finance reports from connected business data"}',
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
                        'product_page_tagline' => 'Automate recurring finance reports from connected business data',
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
            && mb_strlen($prompt) < 3000;
    });
});

test('tagline generation does not retry another ai provider after failure', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'Unavailable']], 503)]);

    $result = app(TaglineRewriterService::class)->rewrite('Ledgerly', 'Finance reporting', 'Context');

    expect($result)->toBeNull();
    Http::assertSentCount(1);
});
