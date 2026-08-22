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

    Http::assertSent(fn ($request): bool => $request['model'] === 'qwen/qwen3.6-27b'
        && $request['reasoning_format'] === 'hidden'
        && $request['max_completion_tokens'] === 4000);
});

test('description rewriter strips model commentary before valid html', function () {
    $items = implode('', array_fill(0, 10, '<li>Grounded item</li>'));
    $html = implode('', [
        '<p><strong>Product summary with specific details.</strong></p><p>Supporting workflow.</p>',
        '<h2><strong>What is Acme?</strong></h2><p>Acme is a product.</p>',
        '<h2><strong>What are the key features of Acme?</strong></h2><ul>'.$items.'</ul>',
        '<h2><strong>Who is Acme best for?</strong></h2><ul><li>Teams</li></ul>',
        '<h2><strong>What can you use Acme for?</strong></h2><ul><li>Work</li></ul>',
        '<h2><strong>How does Acme compare to alternatives?</strong></h2><ul><li>Manual work</li></ul>',
        '<h2><strong>What integrations and ecosystem support does Acme offer?</strong></h2><ul><li>API</li></ul>',
        '<h2><strong>What are the pros of Acme?</strong></h2><ul><li>Specific</li></ul>',
        '<h2><strong>Frequently asked questions about Acme</strong></h2><dl><dt>Question one?</dt><dd>Answer one.</dd><dt>Question two?</dt><dd>Answer two.</dd></dl>',
    ]);

    $method = new ReflectionMethod(DescriptionRewriterService::class, 'cleanHtmlResponse');
    $result = $method->invoke(new DescriptionRewriterService, "Here is my thinking process:\n".$html);

    expect($result)->toStartWith('<p><strong>Product summary');
});
