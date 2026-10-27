<?php

namespace App\Services\PublicPages;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\View;

class PageData
{
    // Execute existing data loaders in a guest context; never dispatch or render an HTML response.
    public function load(string $path): array
    {
        $app = app();
        $originalRequest = $app['request'];
        $originalAuth = $app['auth'];
        $originalSession = $app['session.store'];
        $originalRoute = $app['router']->getCurrentRoute();
        $request = Request::create(app(\App\Support\SiteIdentity::class)->url($path), 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $session = new Store('agent-public', new ArraySessionHandler(120));
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => null);
        $auth = new AuthManager($app);
        $auth->extend('session', fn () => new RequestGuard(fn () => null, $request));

        try {
            $app->instance('request', $request);
            $app->instance('session.store', $session);
            Facade::clearResolvedInstance('session.store');
            Auth::swap($auth);
            $app['url']->setRequest($request);
            $route = app(\App\Services\Markdown\Registry::class)->match($path);
            abort_unless($route, 404);
            $request->setRouteResolver(fn () => $route);
            $app->instance(Route::class, $route);
            $app['router']->substituteBindings($route);
            $app['router']->substituteImplicitBindings($route);
            // Product HTML adds response headers after constructing its shared view.
            $result = $route->getName() === 'products.show'
                ? app(\App\Http\Controllers\ProductController::class)->publicPageView($route->parameter('product'))
                : $route->run();
            abort_unless($result instanceof View, 404);

            return $result->getData();
        } finally {
            $app->instance('request', $originalRequest);
            $app->instance('session.store', $originalSession);
            Facade::clearResolvedInstance('session.store');
            Auth::swap($originalAuth);
            $app['url']->setRequest($originalRequest);
            if ($originalRoute) {
                $app->instance(Route::class, $originalRoute);
            } else {
                $app->forgetInstance(Route::class);
            }
        }
    }
}
