<?php

use App\Services\AiProviderRoutingService;
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
