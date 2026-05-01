<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StorePreviousUrl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $excludedRoutes = [
            'login',
            'register',
            'logout',
            'password.request',
            'password.reset',
            'password.email',
            'password.update',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'auth.magic-link.consume',
            'auth.complete-profile.show',
            'auth.google',
            'auth.google.callback',
            'ads.click',
            'ads.impression',
            'products.click',
        ];

        $acceptHeader = (string) $request->header('accept');
        $expectsHtml = str_contains($acceptHeader, 'text/html')
            || str_contains($acceptHeader, 'application/xhtml+xml');

        if (
            $request->isMethod('GET')
            && ! in_array($request->route()?->getName(), $excludedRoutes, true)
            && ! $request->ajax()
            && $expectsHtml
        ) {
            session(['url.intended' => url()->current()]);
        }

        return $next($request);
    }
}
