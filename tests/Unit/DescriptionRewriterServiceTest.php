<?php

use App\Services\DescriptionRewriterService;
use Illuminate\Support\Facades\Http;

test('description rewriter returns only the first two overview paragraphs', function () {
    config([
        'services.groq.key' => 'test-key',
        'services.groq.base_url' => 'https://api.groq.test/openai/v1',
        'services.groq.model' => 'test-model',
        'services.google.gemini_key' => null,
        'services.openrouter.key' => null,
    ]);

    Http::fake([
        'https://api.groq.test/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => implode("\n", [
                        '<p><strong>Acme helps software teams coordinate releases.</strong></p>',
                        '<p>It centralizes approvals and rollout steps.</p>',
                        '<h2>Key features</h2>',
                        '<ul><li>Release approvals</li></ul>',
                    ]),
                ],
            ]],
        ]),
    ]);

    $result = (new DescriptionRewriterService())->rewrite(
        'Acme',
        'Release management for software teams.',
        'Acme coordinates approvals and rollout steps.'
    );

    expect($result)->toBe(implode("\n", [
        '<p><strong>Acme helps software teams coordinate releases.</strong></p>',
        '<p>It centralizes approvals and rollout steps.</p>',
    ]));
    expect($result)->not->toContain('<h2>')->not->toContain('<ul>');
});

test('default prompt requests overview only', function () {
    $method = new ReflectionMethod(DescriptionRewriterService::class, 'buildPrompt');
    $prompt = $method->invoke(
        new DescriptionRewriterService(),
        'Acme',
        'Release management for engineering teams.',
        'Categories and pricing are extracted elsewhere.',
        null
    );

    expect($prompt)
        ->toContain('one or two <p> paragraphs')
        ->toContain('Do not add headings, lists, key features')
        ->not->toContain('HTML STRUCTURE TO FOLLOW EXACTLY')
        ->not->toContain('Frequently asked questions about');
});

test('description rewriter fallback returns overview paragraphs without sections', function () {
    config([
        'services.groq.key' => null,
        'services.google.gemini_key' => null,
        'services.openrouter.key' => null,
    ]);

    $result = (new DescriptionRewriterService())->rewrite(
        'Acme',
        'A release management tool for software teams.',
        "BODY CONTENT:\nAcme centralizes deployment approvals and rollout steps for engineering teams."
    );

    expect($result)
        ->toContain('<p>')
        ->not->toContain('<h2>')
        ->not->toContain('<ul>')
        ->not->toContain('<dl>');
});
