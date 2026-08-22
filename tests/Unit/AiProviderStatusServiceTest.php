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
