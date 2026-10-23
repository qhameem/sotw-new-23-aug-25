<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSharedPageCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        if (str_contains($contentType, 'text/html') || str_contains($contentType, 'json')
            || $response->isRedirection() || $response->getStatusCode() >= 400) {
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, max-age=0, must-revalidate');
            $response->headers->set('X-LiteSpeed-Cache-Control', 'no-cache');
            $response->headers->remove('Surrogate-Control');
            $response->setVary(['Cookie', 'Authorization'], false);
            $response->setExpires(new \DateTimeImmutable('@0'));
        }

        return $response;
    }
}
