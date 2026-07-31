<?php

use App\Services\AiProviderRoutingService;
use Illuminate\Support\Facades\Cache;

test('gemini is the primary configured ai provider', function () {
    config([
        'services.google.api_key' => 'test-gemini-key',
        'services.groq.key' => 'test-groq-key',
        'services.openrouter.key' => 'test-openrouter-key',
    ]);

    Cache::clear();

    $providers = app(AiProviderRoutingService::class)
        ->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']);

    expect(array_column($providers, 'provider'))->toBe([
        'gemini',
        'groq',
        'openrouter',
    ]);
});
