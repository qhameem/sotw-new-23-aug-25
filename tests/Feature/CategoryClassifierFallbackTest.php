<?php

use App\Services\CategoryClassifier;
use App\Services\AiProviderRoutingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::clear();
    config([
        'services.openrouter.key' => 'openrouter-test',
        'services.cloudflare_ai.api_token' => null,
        'services.cloudflare_ai.account_id' => null,
        'services.groq.key' => 'groq-test',
        'services.google.api_key' => null,
    ]);
});

test('classifier retries another provider when the first returns invalid JSON', function () {
    Http::fake([
        'api.openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'I cannot classify this.']]]]),
        'api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'categories' => ['Website Builders'],
            'use_cases' => ['Website Creation'],
            'best_for' => [],
            'pricing' => ['Subscription'],
            'platforms' => ['Web'],
        ])]]]]),
    ]);

    $result = app(CategoryClassifier::class)->classify('A website builder for teams.');

    expect($result['categories'])->toBe(['Website Builders'])
        ->and($result['pricing'])->toBe(['Subscription'])
        ->and($result)->not->toHaveKey('error');
    Http::assertSentCount(2);
});

test('classifier retries another provider after a request exception', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'openrouter.ai')) {
            throw new RuntimeException('Connection timed out');
        }

        return Http::response(['choices' => [['message' => ['content' => json_encode([
            'categories' => ['Website Builders'],
            'use_cases' => [],
            'best_for' => [],
            'pricing' => [],
            'platforms' => [],
        ])]]]]);
    });

    $result = app(CategoryClassifier::class)->classify('A website builder for teams.');

    expect($result['categories'])->toBe(['Website Builders'])
        ->and($result)->not->toHaveKey('error');
    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.groq.com'));
    expect(array_column(app(AiProviderRoutingService::class)->orderedConfiguredProviders(['openrouter', 'groq']), 'provider'))
        ->toBe(['groq']);
});
