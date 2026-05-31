<?php

namespace App\Http\Middleware;

use App\Support\ToolSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConfiguredToolSlug
{
    public function __construct(private readonly ToolSettings $toolSettings)
    {
    }

    public function handle(Request $request, Closure $next, string $toolKey): Response
    {
        $requestedSlug = $request->route('toolSlug');

        if ($this->toolSettings->isCurrentSlug($toolKey, $requestedSlug)) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            if ($this->toolSettings->isLegacySlug($toolKey, $requestedSlug)) {
                return redirect($this->canonicalPath($request, $toolKey), 301);
            }
        }

        abort(404);
    }

    private function canonicalPath(Request $request, string $toolKey): string
    {
        $segments = $request->segments();

        if (($segments[0] ?? null) === 'tools') {
            $segments[1] = $this->toolSettings->slug($toolKey);
        }

        $path = '/' . implode('/', $segments);
        $queryString = $request->getQueryString();

        return $queryString ? $path . '?' . $queryString : $path;
    }
}
