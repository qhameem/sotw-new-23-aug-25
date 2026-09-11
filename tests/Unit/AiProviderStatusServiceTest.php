<?php

use App\Services\AiProviderStatusService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('groq status probe uses the configured model and endpoint', function () {
    config([
        'services.groq.key' => 'test-groq-key',
        'services.groq.model' => 'qwen/qwen3.6-27b',
        'services.groq.base_url' => 'https://api.groq.com/openai/v1',
        'services.google.api_key' => null,
        'services.openrouter.key' => null,
        'services.cerebras.key' => null,
        'services.cloudflare_ai.api_token' => null,
        'services.cloudflare_ai.account_id' => null,
    ]);

    Cache::clear();
    Http::fake([
        'https://api.groq.com/openai/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'pong']]],
        ], 200),
    ]);

    $snapshots = collect(app(AiProviderStatusService::class)->refreshSnapshots())->keyBy('provider');

    expect($snapshots['groq']['state'])->toBe('ok')
        ->and($snapshots['groq']['model'])->toBe('qwen/qwen3.6-27b');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
        && $request['model'] === 'qwen/qwen3.6-27b');
});

test('cerebras and cloudflare status probes use their configured endpoints', function () {
    config([
        'services.groq.key' => null,
        'services.google.api_key' => null,
        'services.openrouter.key' => null,
        'services.cerebras.key' => 'cerebras-key',
        'services.cerebras.base_url' => 'https://api.cerebras.test/v1',
        'services.cloudflare_ai.api_token' => 'cloudflare-token',
        'services.cloudflare_ai.account_id' => 'account-id',
        'services.cloudflare_ai.base_url' => 'https://api.cloudflare.test/client/v4/accounts',
    ]);

    Cache::clear();
    Http::fake(['*' => Http::response([
        'choices' => [['message' => ['content' => 'OK']]],
    ])]);

    $snapshots = collect(app(AiProviderStatusService::class)->refreshSnapshots())->keyBy('provider');

    expect($snapshots['cerebras']['state'])->toBe('ok')
        ->and($snapshots['cloudflare']['state'])->toBe('ok');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.cerebras.test/v1/chat/completions');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.cloudflare.test/client/v4/accounts/account-id/ai/v1/chat/completions');
});
