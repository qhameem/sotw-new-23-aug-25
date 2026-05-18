<?php

use App\Services\DescriptionRewriterService;
use Illuminate\Support\Facades\Http;

test('description rewriter omits faq instructions when the source is too thin', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

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
            && str_contains($prompt, 'Avoid vague audience labels like "visual storytellers", "teams", "professionals", or "creators" unless the source material clearly uses or supports them.')
            && str_contains($prompt, 'Avoid unverified quality claims like "accurate", "reliable", "powerful", or "seamless" unless the source explicitly supports them.')
            && str_contains($prompt, 'When mentioning integrations or supported software, name the actual products from the source material instead of vague phrases like "popular editing software".')
            && str_contains($prompt, 'Help a user decide quickly without overwhelming them with unnecessary detail.')
            && str_contains($prompt, 'AEO / AI SEARCH RULES:')
            && str_contains($prompt, 'The first paragraph should be about 40-60 words so it can be extracted cleanly by AI engines.')
            && str_contains($prompt, 'If pricing, support, integrations, or alternatives are not clearly supported by the source material, avoid mentioning specific details about them.')
            && str_contains($prompt, 'Limitations must be based on explicit source material.')
            && str_contains($prompt, 'Preserve the exact HTML structure, section order, headings, and list types shown below')
            && str_contains($prompt, '<h2><strong>What is Acme?</strong></h2>')
            && str_contains($prompt, '<h2><strong>What are the key features of Acme?</strong></h2>')
            && !str_contains($prompt, '<h2><strong>What are the top use cases for Acme?</strong></h2>')
            && str_contains($prompt, 'Do NOT add an alternatives or comparison section for this product.')
            && !str_contains($prompt, '<h2><strong>How does Acme compare to alternatives?</strong></h2>')
            && str_contains($prompt, 'Do NOT add an integrations or ecosystem section for this product.')
            && !str_contains($prompt, '<h2><strong>What integrations and ecosystem support does Acme offer?</strong></h2>')
            && str_contains($prompt, 'Do NOT add a FAQ section for this product.')
            && !str_contains($prompt, '<h2><strong>Frequently asked questions about Acme</strong></h2>')
            && str_contains($prompt, 'If no grounded limitation is supported, say "Not clearly stated in the available source material."')
            && str_contains($prompt, 'Does each section include concrete details that are specific to Acme rather than generic SaaS filler?')
            && str_contains($prompt, 'Mention "Acme" naturally in the opening paragraph.')
            && str_contains($prompt, 'Would a user understand what the product is and whether it might fit them within 30 seconds?');
    });
});

test('description rewriter keeps faq instructions when the source has strong faq signals', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

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

    $service->rewrite(
        'Acme',
        'Release management for software teams with deployment approvals, environment controls, and audit trails.',
        implode("\n", [
            'Title: Acme release management',
            'H1: Ship production releases with approvals and rollback controls',
            'H2: What integrations does Acme support?',
            'H2: How does pricing work for engineering teams?',
            'H2: Compare Acme with manual release checklists',
            'BODY CONTENT: Acme includes API access, Slack alerts, webhook triggers, GitHub deployment approvals, environment controls, release rollback steps, audit trails, onboarding guides, setup docs, and support articles for growing software teams. Teams can compare plans, review pricing, connect integrations, and follow documented workflows for production releases, change approvals, incident response, and post-release verification. The documentation explains how engineering managers, developers, and platform teams use Acme, what integrations are available, how the deployment workflow works, and how customers move from manual release checklists to a structured release process.',
        ])
    );

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return str_contains($prompt, 'Include the FAQ section because the source material appears detailed enough to support at least 2 grounded, non-generic user questions.')
            && str_contains($prompt, 'Make the FAQ questions sound like real user search queries.')
            && str_contains($prompt, 'Include the alternatives section because the source material contains grounded comparison signals or named alternatives.')
            && str_contains($prompt, '<h2><strong>How does Acme compare to alternatives?</strong></h2>')
            && str_contains($prompt, 'Include the integrations section because the source material contains grounded ecosystem, API, or integration details.')
            && str_contains($prompt, '<h2><strong>What integrations and ecosystem support does Acme offer?</strong></h2>')
            && str_contains($prompt, '<h2><strong>Frequently asked questions about Acme</strong></h2>')
            && str_contains($prompt, '<h2><strong>What is Acme?</strong></h2>')
            && str_contains($prompt, 'Do NOT ask about pricing, customer support, integrations, or alternatives unless the source material clearly supports those details.')
            && str_contains($prompt, 'Are the FAQ questions and answers limited to facts that are clearly supported?')
            && str_contains($prompt, 'Are the limitations explicitly supported by the source rather than inferred from what similar tools usually struggle with?')
            && str_contains($prompt, 'Did you avoid vague audience labels, generic praise, and broad integration wording when the source supports something more specific?');
    });
});

test('description rewriter keeps comparisons but omits integrations when the source supports one and not the other', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        '*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '  <p><strong>Magic Notebook is a calm writing app for people who just want to write.</strong></p>  ',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $service->rewrite(
        'Magic Notebook',
        'A calm app for people who just want to write.',
        implode("\n", [
            'Title: Magic Notebook',
            'H1: Magic Notebook Write your way. Write better',
            'H2: Write. Don\'t learn software',
            'H2: Why Magic Notebook exists?',
            'H2: Compare for yourself',
            'BODY CONTENT: Magic Notebook is a simple alternative for notes, articles, and documents. It works with DOCX, Markdown, and TXT files stored in regular folders on your computer. The site compares Magic Notebook with Word, Google Docs, and Notion on simplicity, cloud requirements, long-form writing, and price. Footer links include Contact Us, Support the Project, Privacy Policy, and Terms of Service.',
        ])
    );

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return str_contains($prompt, '<h2><strong>How does Magic Notebook compare to alternatives?</strong></h2>')
            && !str_contains($prompt, '<h2><strong>What integrations and ecosystem support does Magic Notebook offer?</strong></h2>')
            && str_contains($prompt, 'Do NOT add an integrations or ecosystem section for this product.')
            && str_contains($prompt, 'Do NOT add a FAQ section for this product.');
    });
});

test('description rewriter prefers gemini when a google api key is available', function () {
    config([
        'services.google.api_key' => 'test-google-key',
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => "```html\n<p><strong>Acme helps teams ship updates without the usual mess.</strong></p>\n```",
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '<p><strong>Groq fallback should not be used here.</strong></p>',
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
        return $request->url() === 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent'
            && $request->hasHeader('X-goog-api-key', 'test-google-key');
    });

    Http::assertNotSent(function ($request) {
        return $request->url() === 'https://api.groq.com/openai/v1/chat/completions';
    });
});
