<?php

use App\Support\ExpandableEditorialLists;

test('editorial lists show remaining items once without losing other content', function () {
    $html = '<p>Additional context.</p><h3>Key Features</h3><ul>';
    for ($i = 1; $i <= 6; $i++) {
        $html .= '<li>Feature '.$i.'</li>';
    }
    $html .= '</ul><h3>Integrations</h3><p>HTTP API.</p>';

    $result = ExpandableEditorialLists::render($html);

    expect($result)->toContain('<details>', 'Show more', 'Additional context.', 'HTTP API.');
    expect(strpos($result, 'Feature 4'))->toBeLessThan(strpos($result, '<details>'));
    expect(strpos($result, 'Feature 5'))->toBeGreaterThan(strpos($result, '<details>'));
    for ($i = 1; $i <= 6; $i++) {
        expect(substr_count($result, 'Feature '.$i))->toBe(1);
    }
});

test('short lists remain visible and ordered lists retain numbering', function () {
    expect(ExpandableEditorialLists::render('<ul><li>One</li></ul>'))->not->toContain('<details>');
    $html = '<ol start="3"><li>A</li><li>B</li><li>C</li><li>D</li><li>E</li></ol>';
    expect(ExpandableEditorialLists::render($html))->toContain('<ol start="7">');
});
