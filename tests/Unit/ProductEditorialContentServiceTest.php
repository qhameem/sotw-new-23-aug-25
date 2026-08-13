<?php

use App\Services\ProductEditorialContentService;

test('product editorial content service ignores placeholder limitations', function () {
    $service = new ProductEditorialContentService();

    $parsed = $service->parseHtml(implode("\n", [
        '<p>Acme keeps release planning organized.</p>',
        '<p>It gives teams one place to review rollout work.</p>',
        '<h2>Pros Cons</h2>',
        '<ul>',
        '<li>Pros: Keeps release steps together; Makes approvals easier to track</li>',
        '<li>Limitations: Not clearly stated in the available source material.</li>',
        '</ul>',
    ]));

    expect($parsed['pros'])->toBe([
        'Keeps release steps together',
        'Makes approvals easier to track',
    ]);

    expect($parsed['limitations'])->toBe([]);
});

test('product editorial content service extracts generated question based sections and faqs', function () {
    $service = new ProductEditorialContentService();

    $parsed = $service->parseHtml(implode("\n", [
        '<p><strong>Acme manages releases for software teams.</strong></p>',
        '<p>It centralizes rollout work.</p>',
        '<h2><strong>What are the key features of Acme?</strong></h2>',
        '<ul><li>Approval workflows</li><li>Rollback guidance</li><li>Release visibility</li></ul>',
        '<h2><strong>Who is Acme best for?</strong></h2>',
        '<ul><li>Software teams</li></ul>',
        '<h2><strong>Frequently asked questions about Acme</strong></h2>',
        '<dl><dt><strong>Does Acme support approvals?</strong></dt><dd>Yes, it supports approval workflows.</dd></dl>',
    ]));

    expect($parsed['key_features'])->toHaveCount(3)
        ->and($parsed['ideal_for'])->toBe(['Software teams'])
        ->and($parsed['faq'])->toBe([[
            'question' => 'Does Acme support approvals?',
            'answer' => 'Yes, it supports approval workflows.',
        ]]);
});
