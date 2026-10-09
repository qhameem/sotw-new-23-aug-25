<?php

test('newsletter form renders without shared validation errors', function () {
    $html = view('partials._newsletter-signup-form', ['placement' => 'sidebar'])->render();

    expect($html)->toContain('name="email"');
});
