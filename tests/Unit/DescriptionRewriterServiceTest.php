<?php

use App\Services\DescriptionRewriterService;
use Illuminate\Support\Facades\Http;

test('description rewriter renders deterministic html from structured groq json', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'Acme helps software teams plan and ship releases with approval steps and rollback guidance.',
                            'supporting_sentence' => 'It keeps release work organized without making teams manage deployment steps in spreadsheets.',
                            'what_it_is' => 'Acme is a release management tool for software teams. It centralizes approvals, rollout steps, and release visibility.',
                            'key_features' => [
                                'Release plans with approval steps.',
                                'Rollback guidance for production changes.',
                                'Shared release visibility for engineering teams.',
                            ],
                            'best_for' => [
                                'Software teams coordinating releases across environments.',
                                'Engineering leads who need clearer approval workflows.',
                            ],
                            'pros' => [
                                'Keeps release steps in one place.',
                                'Makes approvals easier to track.',
                            ],
                            'limitations' => [
                                'Not clearly stated in the available source material.',
                            ],
                            'alternatives' => [
                                'Should be ignored when the source does not support comparisons.',
                            ],
                            'integrations' => [
                                'Should be ignored when the source does not support integrations.',
                            ],
                            'faq' => [
                                [
                                    'question' => 'Should be ignored?',
                                    'answer' => 'Yes.',
                                ],
                            ],
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Acme',
        'A release management tool for software teams.',
        '<h1>Release coordination for software teams</h1>'
    );

    expect($result)->toBe(implode("\n", [
        '<p><strong>Acme helps software teams plan and ship releases with approval steps and rollback guidance.</strong></p>',
        '<p>It keeps release work organized without making teams manage deployment steps in spreadsheets.</p>',
        '<h2><strong>What is Acme?</strong></h2>',
        '<p>Acme is a release management tool for software teams. It centralizes approvals, rollout steps, and release visibility.</p>',
        '<h2><strong>What are the key features of Acme?</strong></h2>',
        '<ul><li>Release plans with approval steps.</li><li>Rollback guidance for production changes.</li><li>Shared release visibility for engineering teams.</li></ul>',
        '<h2><strong>Who is Acme best for?</strong></h2>',
        '<ul><li>Software teams coordinating releases across environments.</li><li>Engineering leads who need clearer approval workflows.</li></ul>',
        '<h2><strong>What are the pros of Acme?</strong></h2>',
        '<ul><li>Keeps release steps in one place.</li><li>Makes approvals easier to track.</li></ul>',
    ]));
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
                                'text' => json_encode([
                                    'summary' => 'Acme helps software teams plan releases with clearer approvals and rollout visibility.',
                                    'supporting_sentence' => 'It gives teams a central place to manage release steps.',
                                    'what_it_is' => 'Acme is a release management tool for engineering teams. It organizes rollout steps and approvals.',
                                    'key_features' => [
                                        'Release workflows with approvals.',
                                        'Shared visibility into rollout progress.',
                                        'A single place for release steps.',
                                    ],
                                    'best_for' => [
                                        'Engineering teams shipping coordinated releases.',
                                        'Leads who need approval visibility.',
                                    ],
                                    'pros' => [
                                        'Clarifies release work.',
                                        'Keeps approvals visible.',
                                    ],
                                    'limitations' => [
                                        'Not clearly stated in the available source material.',
                                    ],
                                    'alternatives' => [],
                                    'integrations' => [],
                                    'faq' => [],
                                ], JSON_UNESCAPED_SLASHES),
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
                        'content' => '{"summary":"Groq should not be used."}',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Acme',
        'A release management tool for software teams.',
        '<h1>Release coordination for software teams</h1>'
    );

    expect($result)->toContain('Acme helps software teams plan releases with clearer approvals and rollout visibility.');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent'
            && $request->hasHeader('X-goog-api-key', 'test-google-key')
            && ($request['generationConfig']['responseMimeType'] ?? null) === 'application/json';
    });

    Http::assertNotSent(function ($request) {
        return $request->url() === 'https://api.groq.com/openai/v1/chat/completions';
    });
});

test('description rewriter includes optional sections when the source supports them', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'Acme helps engineering teams manage releases with approvals, integrations, and clearer rollout documentation.',
                            'supporting_sentence' => 'It brings release planning, integrations, and operational guidance into one workflow.',
                            'what_it_is' => 'Acme is a release management product for engineering teams. It helps teams organize approvals, rollout steps, and connected systems.',
                            'key_features' => [
                                'Approvals for production releases.',
                                'Connected rollout workflows with external tools.',
                                'Structured release steps and documentation.',
                            ],
                            'best_for' => [
                                'Platform teams coordinating production releases.',
                                'Engineering managers who need approval visibility.',
                            ],
                            'pros' => [
                                'Centralizes release coordination.',
                                'Keeps integrations and workflow details together.',
                            ],
                            'limitations' => [
                                'Not clearly stated in the available source material.',
                            ],
                            'alternatives' => [
                                'Compared with manual release checklists, it keeps approvals and rollout steps in one place.',
                            ],
                            'integrations' => [
                                'Works with GitHub deployment approvals, Slack alerts, and webhook triggers.',
                            ],
                            'faq' => [
                                [
                                    'question' => 'What integrations does Acme support?',
                                    'answer' => 'The source mentions GitHub deployment approvals, Slack alerts, and webhook triggers.',
                                ],
                                [
                                    'question' => 'How does Acme help with release workflows?',
                                    'answer' => 'It organizes approvals, rollout steps, and release documentation for engineering teams.',
                                ],
                            ],
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Acme',
        'Release management for software teams with deployment approvals and audit trails.',
        implode("\n", [
            'H1: Ship releases with approvals and rollback controls',
            'H2: What integrations does Acme support?',
            'H2: How does pricing work for engineering teams?',
            'H2: Compare Acme with manual release checklists',
            'BODY CONTENT: Acme includes API access, Slack alerts, webhook triggers, GitHub deployment approvals, environment controls, setup docs, release workflows, change approvals, audit trails, rollout checklists, rollback guidance, and onboarding documentation for software teams.',
            'MORE CONTENT: Engineering managers use Acme to review release plans, confirm deployment readiness, coordinate environments, and document release decisions. The documentation explains workflows, setup steps, release policies, deployment approvals, webhook triggers, integration details, and how teams compare manual release checklists with a more structured release process. Teams can review pricing information, documentation, knowledge base guidance, and rollout procedures before shipping production changes.',
        ])
    );

    expect($result)->toContain('<h2><strong>How does Acme compare to alternatives?</strong></h2>');
    expect($result)->toContain('<h2><strong>What integrations and ecosystem support does Acme offer?</strong></h2>');
    expect($result)->toContain('<h2><strong>Frequently asked questions about Acme</strong></h2>');
    expect($result)->toContain('<dt><strong>What integrations does Acme support?</strong></dt>');

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return str_contains($prompt, 'Return ONLY valid JSON.')
            && str_contains($prompt, '- Fill the `alternatives` array with up to 2 grounded comparison bullets.')
            && str_contains($prompt, '- Fill the `integrations` array with specific integrations, APIs, or supported platforms.')
            && str_contains($prompt, '- Fill the `faq` array with up to 2 grounded question/answer pairs.');
    });
});

test('description rewriter omits optional sections when the source does not support them', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'Magic Notebook helps writers work in a calmer desktop environment without cloud-first writing tools.',
                            'supporting_sentence' => 'It focuses on local files, simple formatting, and a distraction-light writing experience.',
                            'what_it_is' => 'Magic Notebook is a desktop writing app. It opens local folders and keeps writing workflows simple.',
                            'key_features' => [
                                'Works with local DOCX, Markdown, and TXT files.',
                                'Supports headings, lists, tables, links, and images.',
                                'Autosaves work while you write.',
                            ],
                            'best_for' => [
                                'Writers who prefer local files over cloud-first tools.',
                                'People who want a simpler writing app.',
                            ],
                            'pros' => [
                                'Keeps files in regular folders.',
                                'Stays focused on writing instead of extra project features.',
                            ],
                            'limitations' => [
                                'Currently only available for macOS, with a Windows version coming soon.',
                            ],
                            'alternatives' => [
                                'Should not render even if the model includes it.',
                            ],
                            'integrations' => [
                                'Should not render even if the model includes it.',
                            ],
                            'faq' => [
                                [
                                    'question' => 'Should this render?',
                                    'answer' => 'No.',
                                ],
                            ],
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Magic Notebook',
        'A desktop writing app for calm, local-first writing.',
        implode("\n", [
            'Title: Magic Notebook',
            'H1: Write your way. Write better.',
            'BODY CONTENT: Works with DOCX, Markdown, and TXT files stored in regular folders on your computer.',
        ])
    );

    expect($result)->not->toContain('How does Magic Notebook compare to alternatives?');
    expect($result)->not->toContain('What integrations and ecosystem support does Magic Notebook offer?');
    expect($result)->not->toContain('Frequently asked questions about Magic Notebook');

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return str_contains($prompt, '- Return `alternatives` as an empty array.')
            && str_contains($prompt, '- Return `integrations` as an empty array.')
            && str_contains($prompt, '- Return `faq` as an empty array.');
    });
});

test('description rewriter falls back to groq when gemini fails', function () {
    config([
        'services.google.api_key' => 'test-google-key',
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'message' => 'temporary failure',
            ],
        ], 500),
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'Acme helps software teams manage releases with clearer rollout steps and approvals.',
                            'supporting_sentence' => 'It gives teams one place to track release work.',
                            'what_it_is' => 'Acme is a release management tool for engineering teams. It organizes release steps and approvals.',
                            'key_features' => [
                                'Shared release steps.',
                                'Approval visibility.',
                                'Centralized rollout tracking.',
                            ],
                            'best_for' => [
                                'Engineering teams coordinating releases.',
                                'Leads who need rollout visibility.',
                            ],
                            'pros' => [
                                'Keeps release work visible.',
                                'Brings approvals into one place.',
                            ],
                            'limitations' => [
                                'Not clearly stated in the available source material.',
                            ],
                            'alternatives' => [],
                            'integrations' => [],
                            'faq' => [],
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Acme',
        'A release management tool for software teams.',
        '<h1>Release coordination for software teams</h1>'
    );

    expect($result)->toContain('Acme helps software teams manage releases with clearer rollout steps and approvals.');

    Http::assertSentCount(2);
});

test('description rewriter returns null when the provider response is not valid json', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => '<p>This is not valid JSON.</p>',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Acme',
        'A release management tool for software teams.',
        '<h1>Release coordination for software teams</h1>'
    );

    expect($result)->toBeNull();
});

test('description rewriter normalizes product names, casing, and weak limitation guesses', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'wowable turns links into websites for businesses using google maps and tripadvisor listings.',
                            'supporting_sentence' => 'you can paste a link and get a site without design work.',
                            'what_it_is' => 'wowable is a tool that creates websites from links. it works with google maps, tripadvisor, facebook, instagram, and linkedin.',
                            'key_features' => [
                                'works with google maps and tripadvisor listings.',
                                'offers a free preview before publishing.',
                                'removes the need for design work.',
                            ],
                            'best_for' => [
                                'businesses with existing profiles on google maps.',
                                'people who want a website without design work.',
                            ],
                            'pros' => [
                                'easy to use.',
                                'free preview available.',
                            ],
                            'limitations' => [
                                'only works with links from specific platforms.',
                            ],
                            'alternatives' => [],
                            'integrations' => [],
                            'faq' => [],
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Instant AI Website from Your Google Reviews & Links | Wowable',
        'Turn listings and profiles into websites.',
        'Title: Instant AI Website from Your Google Reviews & Links | Wowable'
    );

    expect($result)->toContain('<h2><strong>What is Wowable?</strong></h2>');
    expect($result)->toContain('Wowable turns links into websites for businesses using Google Maps and TripAdvisor listings.');
    expect($result)->toContain('It works with Google Maps, TripAdvisor, Facebook, Instagram, and LinkedIn.');
    expect($result)->toContain('<h2><strong>What are the pros of Wowable?</strong></h2>');
    expect($result)->not->toContain('Not clearly stated in the available source material.');
});

test('description rewriter does not uppercase ai inside unrelated words', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'changelogfy helps teams manage feedback.',
                            'supporting_sentence' => 'it makes maintaining changelogs easier for product teams.',
                            'what_it_is' => 'changelogfy is a feedback and changelog tool. it helps teams maintain update pages and collect feedback.',
                            'key_features' => [
                                'central place for feedback.',
                                'changelog publishing.',
                                'knowledge base support.',
                            ],
                            'best_for' => [
                                'product teams.',
                                'software companies.',
                            ],
                            'pros' => [
                                'helps with maintaining changelogs.',
                                'keeps feedback organized.',
                            ],
                            'limitations' => [
                                'not clearly stated in the available source material.',
                            ],
                            'alternatives' => [],
                            'integrations' => [],
                            'faq' => [],
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Changelogfy',
        'Feedback and changelog management.',
        'Title: Changelogfy'
    );

    expect($result)->toContain('maintaining changelogs');
    expect($result)->not->toContain('mAInt');
});

test('description rewriter refines generic editorial fields with a second pass', function () {
    config([
        'services.google.api_key' => 'test-google-key',
        'services.groq.key' => null,
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::sequence()
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'summary' => 'Wowable helps businesses and individuals create an online presence quickly from existing links.',
                                        'supporting_sentence' => 'It turns public links into a website with a free preview.',
                                        'what_it_is' => 'Wowable is a tool that creates websites from links on popular platforms. It is quick and easy to use.',
                                        'key_features' => [
                                            'Creates a website from existing links.',
                                            'Offers a free preview before publishing.',
                                            'Removes the need for design work.',
                                        ],
                                        'best_for' => [
                                            'Small businesses with existing listings or profiles.',
                                            'People who want a website without design work.',
                                        ],
                                        'pros' => [
                                            'Quick and easy to use.',
                                            'Supports multiple platforms.',
                                        ],
                                        'limitations' => [
                                            'Not clearly stated in the available source material.',
                                        ],
                                        'alternatives' => [],
                                        'integrations' => [],
                                        'faq' => [],
                                    ], JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'summary' => 'Wowable creates a website from existing Google Maps, TripAdvisor, Facebook, Instagram, or LinkedIn links, with a free preview before publishing.',
                                        'what_it_is' => 'Wowable turns existing listing and profile links into a website. You paste a supported link and it builds the site for you.',
                                        'pros' => [
                                            'Builds a site from listings and profiles you already maintain.',
                                            'Lets you preview the generated site before publishing.',
                                        ],
                                    ], JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Wowable',
        'Turn Google Maps, TripAdvisor, Facebook, Instagram, or LinkedIn links into a website with a free preview.',
        'Title: Wowable'
    );

    expect($result)->toContain('Wowable creates a website from existing Google Maps, TripAdvisor, Facebook, Instagram, or LinkedIn links, with a free preview before publishing.');
    expect($result)->toContain('Wowable turns existing listing and profile links into a website. You paste a supported link and it builds the site for you.');
    expect($result)->toContain('Builds a site from listings and profiles you already maintain.');
    expect($result)->not->toContain('Quick and easy to use.');

    Http::assertSentCount(2);
    Http::assertSent(function ($request) {
        $text = $request['contents'][0]['parts'][0]['text'] ?? '';

        return str_contains($text, 'Rewrite only `summary`, `what_it_is`, and `pros`.');
    });
});

test('description rewriter repairs low-quality editorial fields after refinement', function () {
    config([
        'services.google.api_key' => 'test-google-key',
        'services.groq.key' => null,
    ]);

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::sequence()
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'summary' => 'Wowable helps businesses build an online presence from existing links in seconds.',
                                        'supporting_sentence' => 'It creates a website from public links with a free preview.',
                                        'what_it_is' => 'Wowable is a tool that supports links from various platforms. It builds a website for you.',
                                        'key_features' => [
                                            'Creates a website from existing links.',
                                            'Offers a free preview before publishing.',
                                            'Removes the need for design work.',
                                        ],
                                        'best_for' => [
                                            'Small businesses with existing listings or profiles.',
                                            'People who want a website without design work.',
                                        ],
                                        'pros' => [
                                            'Supports multiple platforms.',
                                            'Free preview is available.',
                                        ],
                                        'limitations' => [
                                            'Not clearly stated in the available source material.',
                                        ],
                                        'alternatives' => [],
                                        'integrations' => [],
                                        'faq' => [],
                                    ], JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'summary' => 'Wowable creates a website from existing listing and profile links, with a free preview before publishing.',
                                        'what_it_is' => 'Wowable turns links from various platforms into a website. It builds the site for you.',
                                        'pros' => [
                                            'Builds a site from listings and profiles you already maintain.',
                                            'Lets you preview the generated site before publishing.',
                                        ],
                                    ], JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'what_it_is' => 'Wowable turns links you already maintain into a website. It uses existing listing or profile content instead of starting from scratch.',
                                    ], JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'pros' => [
                                            'Builds a site from listings and profiles you already maintain.',
                                            'Lets you preview the generated site before publishing.',
                                        ],
                                    ], JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Wowable',
        'Turn Google Maps, TripAdvisor, Facebook, Instagram, or LinkedIn links into a website with a free preview.',
        'Title: Wowable'
    );

    expect($result)->toContain('Wowable creates a website from existing listing and profile links, with a free preview before publishing.');
    expect($result)->toContain('Wowable turns links you already maintain into a website. It uses existing listing or profile content instead of starting from scratch.');
    expect($result)->toContain('Builds a site from listings and profiles you already maintain.');
    expect($result)->not->toContain('online presence');
    expect($result)->not->toContain('supports various platforms');
    expect($result)->not->toContain('Supports multiple platforms.');

    Http::assertSentCount(3);
    Http::assertSent(function ($request) {
        $text = $request['contents'][0]['parts'][0]['text'] ?? '';

        return str_contains($text, 'You are fixing the `what_it_is` field');
    });
});

test('description rewriter adds product-type guidance for link-to-website tools', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'summary' => 'Wowable creates a website from existing listings and profiles.',
                            'supporting_sentence' => 'It uses source content you already maintain and offers a free preview.',
                            'what_it_is' => 'Wowable turns existing listing and profile links into a website. It reuses business details and reviews from those sources.',
                            'key_features' => [
                                'Builds a website from source content you already maintain.',
                                'Offers a free preview before publishing.',
                                'Reduces manual setup for basic site creation.',
                            ],
                            'best_for' => [
                                'Small businesses with existing listings or profiles.',
                                'People who want a site built from current business details.',
                            ],
                            'pros' => [
                                'Reuses listings and profiles you already maintain.',
                                'Lets you preview the generated site before publishing.',
                            ],
                            'limitations' => [
                                'Not clearly stated in the available source material.',
                            ],
                            'alternatives' => [],
                            'integrations' => [],
                            'faq' => [],
                        ], JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $service->rewrite(
        'Wowable',
        'Turn Google Maps, TripAdvisor, Facebook, Instagram, or LinkedIn links into a website with a free preview.',
        'Paste your link to create a website from reviews, listings, profiles, and existing social media content.'
    );

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return str_contains($prompt, 'This product appears to turn existing listings, profiles, reviews, or links into a website.')
            && str_contains($prompt, 'Describe the transformation from existing business content to a ready-to-use site.');
    });
});
