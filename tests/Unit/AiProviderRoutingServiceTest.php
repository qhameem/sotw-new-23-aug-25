<?php

use App\Services\AiProviderRoutingService;
use App\Services\ListingAiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('production providers follow the configured reliability order', function () {
    config([
        'services.google.api_key' => 'test-gemini-key',
        'services.groq.key' => 'test-groq-key',
        'services.openrouter.key' => 'test-openrouter-key',
        'services.cloudflare_ai.api_token' => 'test-cloudflare-token',
        'services.cloudflare_ai.account_id' => 'test-account-id',
    ]);

    Cache::clear();

    $providers = app(AiProviderRoutingService::class)
        ->orderedConfiguredProviders(['groq', 'gemini', 'cloudflare', 'openrouter']);

    expect(array_column($providers, 'provider'))->toBe([
        'openrouter',
        'cloudflare',
        'groq',
        'gemini',
    ]);
});

test('provider models can be configured', function () {
    config([
        'services.google.gemini_model' => 'gemini-test',
        'services.groq.model' => 'groq-test',
        'services.openrouter.model' => 'openrouter/free',
        'services.cerebras.model' => 'cerebras-test',
        'services.cloudflare_ai.model' => 'cloudflare-test',
    ]);

    $router = app(AiProviderRoutingService::class);

    expect($router->modelFor('gemini'))->toBe('gemini-test')
        ->and($router->modelFor('groq'))->toBe('groq-test')
        ->and($router->modelFor('openrouter'))->toBe('openrouter/free')
        ->and($router->modelFor('cerebras'))->toBe('cerebras-test')
        ->and($router->modelFor('cloudflare'))->toBe('cloudflare-test');
});

test('temporarily unavailable providers are skipped', function () {
    config([
        'services.google.api_key' => 'test-gemini-key',
        'services.groq.key' => 'test-groq-key',
        'services.openrouter.key' => null,
    ]);

    Cache::clear();
    Http::fake(['*' => Http::response(['error' => ['message' => 'Unauthorized']], 401)]);

    $router = app(AiProviderRoutingService::class);
    $router->recordHttpFailure('gemini', Http::get('https://example.test'));

    expect(array_column($router->orderedConfiguredProviders(['gemini', 'groq']), 'provider'))
        ->toBe(['groq']);
});

test('listing requests record rate limits and skip the limited provider', function () {
    config([
        'services.groq.key' => 'test-groq-key',
        'services.google.api_key' => null,
        'services.openrouter.key' => null,
        'services.cloudflare_ai.api_token' => null,
    ]);
    Cache::clear();
    Http::fake(['*' => Http::response(['error' => ['message' => 'Rate limit']], 429)]);

    $client = app(ListingAiClient::class);
    expect($client->json('Return JSON only.'))->toBeNull();
    expect(app(AiProviderRoutingService::class)->orderedConfiguredProviders(['groq']))->toBe([]);
    expect($client->json('Return JSON only.'))->toBeNull();
    Http::assertSentCount(1);
});

test('listing requests identify the app to OpenRouter', function () {
    config([
        'app.url' => 'https://softwareontheweb.com',
        'app.name' => 'Software on the Web',
        'services.openrouter.key' => 'test-openrouter-key',
        'services.groq.key' => null,
        'services.google.api_key' => null,
        'services.cloudflare_ai.api_token' => null,
        'services.cerebras.key' => null,
    ]);
    Cache::clear();
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]])]);

    expect(app(ListingAiClient::class)->json('Return JSON only.'))->toBe(['ok' => true]);
    Http::assertSent(fn ($request) => $request->hasHeader('HTTP-Referer', 'https://softwareontheweb.com')
        && $request->hasHeader('X-OpenRouter-Title', 'Software on the Web'));
});
