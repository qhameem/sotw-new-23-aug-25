<?php

use App\Services\AiProviderRoutingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('openrouter is followed by groq and gemini', function () {
    config([
        'services.google.api_key' => 'test-gemini-key',
        'services.groq.key' => 'test-groq-key',
        'services.openrouter.key' => 'test-openrouter-key',
    ]);

    Cache::clear();

    $providers = app(AiProviderRoutingService::class)
        ->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']);

    expect(array_column($providers, 'provider'))->toBe([
        'openrouter',
        'groq',
        'gemini',
    ]);
});

test('provider models can be configured', function () {
    config([
        'services.google.gemini_model' => 'gemini-test',
        'services.groq.model' => 'groq-test',
        'services.openrouter.model' => 'openrouter/free',
    ]);

    $router = app(AiProviderRoutingService::class);

    expect($router->modelFor('gemini'))->toBe('gemini-test')
        ->and($router->modelFor('groq'))->toBe('groq-test')
        ->and($router->modelFor('openrouter'))->toBe('openrouter/free');
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
