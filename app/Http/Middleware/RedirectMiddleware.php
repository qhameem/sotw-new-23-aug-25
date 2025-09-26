<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $scheme = $request->getScheme();

        // Check for 'www' and redirect to non-www
        if (preg_match('/^www\./', $host)) {
            $newHost = preg_replace('/^www\./', '', $host);
            return redirect()->to('https://' . $newHost . $request->getRequestUri(), 301);
        }

        // Check for http and redirect to https
        if ($scheme === 'http') {
            return redirect()->to('https://' . $host . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
