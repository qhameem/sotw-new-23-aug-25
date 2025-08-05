<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('AdminMiddleware: Checking authentication and admin status.');

        if (!Auth::check()) {
            Log::warning('AdminMiddleware: User not authenticated.', [
                'ip_address' => $request->ip(),
                'path' => $request->path(),
            ]);
            abort(403, 'Unauthorized action: Not authenticated.');
        }

        $user = Auth::user();
        Log::info('AdminMiddleware: User authenticated.', [
            'user_id' => $user->id,
            'email' => $user->email,
            'is_admin_method_exists' => method_exists($user, 'is_admin'),
        ]);

        if (!method_exists($user, 'is_admin') || !$user->is_admin()) {
            Log::warning('AdminMiddleware: Authenticated user is not an admin.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'path' => $request->path(),
                'roles' => $user->roles->pluck('name')->toArray() ?? 'No roles found',
            ]);
            abort(403, 'Unauthorized action: Not an admin.');
        }

        Log::info('AdminMiddleware: User is authenticated and is an admin. Proceeding.', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $next($request);
    }
}
