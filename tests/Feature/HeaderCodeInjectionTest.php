<?php

use App\Models\CodeSnippet;
use App\Support\HeaderCodeInjection;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;

it('returns saved header code for public requests', function () {
    Storage::fake('local');
    Storage::disk('local')->put('settings.json', json_encode([
        'google_analytics_code' => '<script src="https://example.com/widget.js"></script>',
    ]));

    $request = Request::create('/', 'GET');
    $request->setRouteResolver(fn () => (new Route('GET', '/', []))->name('home'));

    expect(app(HeaderCodeInjection::class)->forRequest($request))
        ->toBe('<script src="https://example.com/widget.js"></script>');
});

it('excludes saved header code from admin requests', function () {
    Storage::fake('local');
    Storage::disk('local')->put('settings.json', json_encode([
        'google_analytics_code' => '<script src="https://example.com/widget.js"></script>',
    ]));

    $request = Request::create('/admin/settings', 'GET');
    $request->setRouteResolver(fn () => (new Route('GET', '/admin/settings', []))->name('admin.settings.index'));

    expect(app(HeaderCodeInjection::class)->forRequest($request))->toBe('');
});

it('excludes saved header code from the add product page', function () {
    Storage::fake('local');
    Storage::disk('local')->put('settings.json', json_encode([
        'google_analytics_code' => '<script src="https://example.com/widget.js"></script>',
    ]));

    $request = Request::create('/add-product', 'GET');
    $request->setRouteResolver(fn () => (new Route('GET', '/add-product', []))->name('products.create'));

    expect(app(HeaderCodeInjection::class)->forRequest($request))->toBe('');
});

it('excludes advertising head snippets from admin and add product pages', function () {
    $snippet = new CodeSnippet([
        'page' => 'all',
        'location' => 'head',
        'code' => '<script src="https://example.com/ad.js"></script>',
    ]);

    $adminRequest = Request::create('/admin/advertising', 'GET');
    $adminRequest->setRouteResolver(fn () => (new Route('GET', '/admin/advertising', []))->name('admin.advertising.index'));

    $addProductRequest = Request::create('/add-product', 'GET');
    $addProductRequest->setRouteResolver(fn () => (new Route('GET', '/add-product', []))->name('products.create'));

    expect($snippet->shouldRenderFor($adminRequest))->toBeFalse()
        ->and($snippet->shouldRenderFor($addProductRequest))->toBeFalse();
});

it('keeps non-head advertising snippets eligible on excluded pages', function () {
    $snippet = new CodeSnippet([
        'page' => 'all',
        'location' => 'body',
        'code' => '<div>Non-header snippet</div>',
    ]);

    $request = Request::create('/add-product', 'GET');
    $request->setRouteResolver(fn () => (new Route('GET', '/add-product', []))->name('products.create'));

    expect($snippet->shouldRenderFor($request))->toBeTrue();
});

it('does not deliver advertising head snippets to excluded pages', function (string $routeName, string $path) {
    CodeSnippet::create([
        'page' => 'all',
        'location' => 'head',
        'code' => '<script src="https://example.com/ad.js"></script>',
        'excluded_ips' => [],
        'excluded_countries' => [],
    ]);

    $this->getJson(route('api.deferred-assets', [
        'route_name' => $routeName,
        'path' => $path,
    ]))
        ->assertOk()
        ->assertJsonPath('head_snippets', []);
})->with([
    'admin dashboard' => ['admin.advertising.index', '/admin/advertising'],
    'add product' => ['products.create', '/add-product'],
]);

it('renders saved header code in the shared head partial', function () {
    Storage::fake('local');
    Storage::disk('local')->put('settings.json', json_encode([
        'google_analytics_code' => '<script async src="https://startupbar.co/widget/loader.js"></script>',
    ]));

    $this->get('/')->assertSee(
        '<script async src="https://startupbar.co/widget/loader.js"></script>',
        escape: false,
    );
});
