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
