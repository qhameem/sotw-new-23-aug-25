<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || filled($user->name)) {
            return $next($request);
        }

        $allowedRoutes = [
            'auth.complete-profile.show',
            'auth.complete-profile.store',
            'logout',
            'profile.destroy',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()->route('auth.complete-profile.show');
    }
}
