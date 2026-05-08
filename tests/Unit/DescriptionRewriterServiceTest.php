<?php

use App\Services\DescriptionRewriterService;
use Illuminate\Support\Facades\Http;

test('description rewriter sends the humanized aeo prompt while preserving the html structure', function () {
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
            && str_contains($prompt, 'ANTI-SLOP QUALITY RULES:')
            && str_contains($prompt, 'Every paragraph and section should contain concrete, product-specific information that would sound wrong if pasted onto a different tool.')
            && str_contains($prompt, 'AEO / AI SEARCH RULES:')
            && str_contains($prompt, 'The first paragraph should be about 40-60 words so it can be extracted cleanly by AI engines.')
            && str_contains($prompt, 'If pricing, support, integrations, or alternatives are not clearly supported by the source material, avoid mentioning specific details about them.')
            && str_contains($prompt, 'Preserve the exact HTML structure, section order, headings, and list types shown below.')
            && str_contains($prompt, '<h2><strong>What are the key features of Acme?</strong></h2>')
            && str_contains($prompt, '<h2><strong>Frequently asked questions about Acme</strong></h2>')
            && str_contains($prompt, 'Do NOT ask about pricing, customer support, integrations, or alternatives unless the source material clearly supports those details.')
            && str_contains($prompt, 'Does each section include concrete details that are specific to Acme rather than generic SaaS filler?')
            && str_contains($prompt, 'Mention "Acme" naturally in the opening paragraph.');
    });
});
