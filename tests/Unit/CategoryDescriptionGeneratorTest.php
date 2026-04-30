<?php

use App\Services\CategoryDescriptionGenerator;
use Illuminate\Support\Facades\Http;

test('category description generator sends a humanized prompt for category seo copy', function () {
    config(['services.groq.key' => 'test-groq-key']);

    Http::fake([
        '*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'description' => 'Email marketing software helps teams send campaigns, automate follow-ups, and keep customer conversations moving without extra manual work. It is a strong fit for growing businesses that need better consistency, reporting, and segmentation across every send.',
                            'meta_description' => 'Find email marketing software that helps teams automate campaigns, segment audiences, and turn routine sends into measurable customer growth.',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new CategoryDescriptionGenerator();

    $result = $service->generate('Email Marketing');

    expect($result)->toBe([
        'description' => 'Email marketing software helps teams send campaigns, automate follow-ups, and keep customer conversations moving without extra manual work. It is a strong fit for growing businesses that need better consistency, reporting, and segmentation across every send.',
        'meta_description' => 'Find email marketing software that helps teams automate campaigns, segment audiences, and turn routine sends into measurable customer growth.',
    ]);

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $request['temperature'] === 0.55
            && str_contains($prompt, 'You are an experienced human writer with 20+ years of experience.')
            && str_contains($prompt, 'HUMAN WRITING RULES:')
            && str_contains($prompt, 'The description and meta description must not sound like rewrites of each other.')
            && str_contains($prompt, 'The meta description should feel like a distinct search snippet written to earn the click.');
    });
});

test('category description generator retries when description and meta description are too similar', function () {
    config(['services.groq.key' => 'test-groq-key']);

    Http::fake([
        '*' => Http::sequence()
            ->push([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'description' => 'Project management software helps teams plan work, track deadlines, and stay aligned across projects. It gives managers and contributors a clearer way to organize tasks and keep delivery moving.',
                                'meta_description' => 'Project management software helps teams plan work, track deadlines, and stay aligned across projects with a clearer way to organize delivery.',
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'choices' => [
                    [
                        'message' => [
                                'content' => json_encode([
                                    'description' => 'Project management software gives teams one place to map timelines, assign work, and keep projects from drifting off course. It is especially useful for growing companies that need more visibility without adding unnecessary process.',
                                    'meta_description' => 'Compare project management software built for clearer planning, better team visibility, and fewer missed deadlines as your team and workload grow.',
                                ], JSON_THROW_ON_ERROR),
                            ],
                        ],
                    ],
            ], 200),
    ]);

    $service = new CategoryDescriptionGenerator();

    $result = $service->generate('Project Management');

    expect($result)->toBe([
        'description' => 'Project management software gives teams one place to map timelines, assign work, and keep projects from drifting off course. It is especially useful for growing companies that need more visibility without adding unnecessary process.',
        'meta_description' => 'Compare project management software built for clearer planning, better team visibility, and fewer missed deadlines as your team and workload grow.',
    ]);

    Http::assertSentCount(2);

    Http::assertSent(function ($request) {
        $prompt = $request['messages'][0]['content'] ?? '';

        return str_contains($prompt, 'RETRY RULES:')
            && str_contains($prompt, 'Use noticeably different wording, rhythm, and sentence construction between the description and the meta description.');
    });
});

test('category description generator repairs short meta descriptions instead of failing', function () {
    config(['services.groq.key' => 'test-groq-key']);

    Http::fake([
        '*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'description' => 'Privacy software helps teams protect sensitive data, tighten access controls, and reduce exposure across everyday workflows. It is especially useful for companies that need stronger safeguards without adding heavy operational friction.',
                            'meta_description' => 'Privacy software for safer data handling and better control.',
                        ], JSON_THROW_ON_ERROR),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new CategoryDescriptionGenerator();

    $result = $service->generate('Privacy');

    expect($result)->not->toBeNull();
    expect($result['description'])->toContain('Privacy software helps teams protect sensitive data');
    expect(strlen($result['meta_description']))->toBeGreaterThanOrEqual(140);
    expect(strlen($result['meta_description']))->toBeLessThanOrEqual(155);
});
