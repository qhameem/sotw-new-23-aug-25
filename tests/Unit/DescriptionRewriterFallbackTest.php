<?php

use App\Services\DescriptionRewriterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('description fallback omits scraped navigation and unsupported generic claims', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => null,
        'services.openrouter.key' => null,
    ]);

    $result = (new DescriptionRewriterService)->rewrite(
        'Cronloop',
        'Cronloop lets you automate recurring work with unattended AI agents that run on a schedule.',
        implode("\n", [
            'H1: Automate recurring work with AI agents.',
            'BODY CONTENT: NewManage your agents from ChatGPT and ClaudeAutomate recurring work with AI agents.',
        ])
    );

    expect($result)
        ->toContain('Cronloop lets you automate recurring work')
        ->not->toContain('NewManage')
        ->not->toContain('Builders who want more structure')
        ->not->toContain('Planning software work')
        ->not->toContain('source material')
        ->not->toContain('compare to alternatives')
        ->not->toContain('<ul></ul>');
});

test('description rewriter uses the configured groq model', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-key',
        'services.groq.model' => 'qwen/qwen3.6-27b',
        'services.openrouter.key' => null,
    ]);

    Cache::clear();
    Http::fake(['https://api.groq.com/*' => Http::response(['error' => ['message' => 'Test failure']], 500)]);

    (new DescriptionRewriterService)->rewrite('Acme', 'Automate recurring work.');

    Http::assertSent(fn ($request): bool => $request['model'] === 'qwen/qwen3.6-27b');
});
