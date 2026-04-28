<?php

use App\Services\DescriptionRewriterService;
use Illuminate\Support\Facades\Http;

test('description rewriter sends the humanized prompt while preserving the html structure', function () {
    config(['services.groq.key' => 'test-groq-key']);

    Http::fake([
        '*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '  <p><strong>Acme helps teams ship updates without the usual mess.</strong></p>  ',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Acme',
        'A release management tool for software teams.',
        '<h1>Release management for fast-moving teams</h1>'
    );

    expect($result)->toBe('<p><strong>Acme helps teams ship updates without the usual mess.</strong></p>');

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $request['temperature'] === 0.55
            && str_contains($prompt, 'You are an experienced human writer with 20+ years of experience.')
            && str_contains($prompt, 'Preserve the exact HTML structure, section order, headings, and list types shown below.')
            && str_contains($prompt, '<h2><strong>Key Features</strong></h2>')
            && str_contains($prompt, '<h2><strong>Frequently Asked Questions</strong></h2>')
            && str_contains($prompt, 'Mention "Acme" naturally in the opening paragraph.');
    });
});
