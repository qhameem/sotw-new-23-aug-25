<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'auth.google',
            'auth.google.callback',
        ];

        if ($request->isMethod('GET') && !in_array($request->route()->getName(), $excludedRoutes) && !$request->ajax()) {
            session(['url.intended' => url()->current()]);
            Log::info('Stored intended URL: ' . url()->current());
        }

        return $next($request);
    }
}
