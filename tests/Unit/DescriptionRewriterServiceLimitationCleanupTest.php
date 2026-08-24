<?php

use App\Services\DescriptionRewriterService;

test('overview cleaner discards legacy limitation and editorial sections', function () {
    $method = new ReflectionMethod(DescriptionRewriterService::class, 'cleanHtmlResponse');
    $html = implode("\n", [
        '<p>Acme manages vendor security reviews.</p>',
        '<h2>Pros and limitations</h2>',
        '<ul><li><strong>Limitations:</strong> Not clearly stated.</li></ul>',
    ]);

    expect($method->invoke(new DescriptionRewriterService(), $html))
        ->toBe('<p>Acme manages vendor security reviews.</p>');
});
