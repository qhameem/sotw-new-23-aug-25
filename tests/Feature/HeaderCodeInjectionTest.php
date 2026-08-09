<?php

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
