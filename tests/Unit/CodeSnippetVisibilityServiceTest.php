<?php

use App\Models\CodeSnippet;
use App\Services\CodeSnippetVisibilityService;
use Illuminate\Http\Request;

test('snippet is hidden when the request ip is excluded', function () {
    $service = new CodeSnippetVisibilityService();
    $snippet = new CodeSnippet([
        'page' => 'all',
        'location' => 'body',
        'code' => '<script></script>',
        'excluded_ips' => ['203.0.113.10'],
        'excluded_countries' => [],
    ]);

    $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);

    expect($service->shouldRender($snippet, $request))->toBeFalse();
});

test('snippet is hidden when the request country is excluded', function () {
    $service = new CodeSnippetVisibilityService();
    $snippet = new CodeSnippet([
        'page' => 'all',
        'location' => 'body',
        'code' => '<script></script>',
        'excluded_ips' => [],
        'excluded_countries' => ['US'],
    ]);

    $request = Request::create('/');
    $request->headers->set('CF-IPCountry', 'US');

    expect($service->shouldRender($snippet, $request))->toBeFalse();
});

test('snippet remains visible when request does not match any exclusion', function () {
    $service = new CodeSnippetVisibilityService();
    $snippet = new CodeSnippet([
        'page' => 'all',
        'location' => 'body',
        'code' => '<script></script>',
        'excluded_ips' => ['203.0.113.10'],
        'excluded_countries' => ['US'],
    ]);

    $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '198.51.100.20']);
    $request->headers->set('CF-IPCountry', 'CA');

    expect($service->shouldRender($snippet, $request))->toBeTrue();
});
