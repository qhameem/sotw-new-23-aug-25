<?php

use App\Services\ProductFactsExtractor;

it('extracts concise facts while excluding duplicated audience and use-case lists', function () {
    $description = <<<'HTML'
<h2>What are the key features?</h2><ul><li>Compares documents side by side</li><li>Provides cited AI answers</li></ul>
<h2>Who is Macro best for?</h2><ul><li>Legal teams</li></ul>
<h2>What can you use Macro for?</h2><ul><li>Contract review</li></ul>
<h2>What integrations does it offer?</h2><ul><li>Works with PDF files</li></ul>
HTML;

    expect(app(ProductFactsExtractor::class)->extract($description))->toBe([
        'Compares documents side by side',
        'Provides cited AI answers',
        'Works with PDF files',
    ]);
});
