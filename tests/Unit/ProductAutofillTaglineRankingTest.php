<?php

use App\Http\Controllers\ProductController;

test('heuristic tagline ranking favors a product summary over a narrow feature heading', function () {
    $controller = (new ReflectionClass(ProductController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($controller, 'buildHeuristicTaglines');

    $result = $method->invoke(
        $controller,
        'Privacy-first analytics that ties every visit to signups and Stripe revenue.',
        'Piqo Analytics — Grow your traffic, search, and revenue',
        [
            'Dead simple, affordable analytics',
            'A launch day you can actually watch.',
            'Every visitor tells their story.',
        ],
        'Piqo Analytics'
    );

    expect($result['tagline'])
        ->toBe('Privacy-first analytics that ties every visit to signups and Stripe revenue.')
        ->not->toBe('A launch day you can actually watch.');
});

test('heuristic tagline fallback rejects product submission calls to action', function () {
    $controller = (new ReflectionClass(ProductController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($controller, 'buildHeuristicTaglines');

    $result = $method->invoke(
        $controller,
        '',
        'Orynth - List your product and earn',
        ['List your product and earn'],
        'Orynth'
    );

    expect($result['tagline'])->toBe('')
        ->and($result['tagline_detailed'])->toBe('');
});
