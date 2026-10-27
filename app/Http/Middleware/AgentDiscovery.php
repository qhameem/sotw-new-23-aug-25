<?php

namespace App\Http\Middleware;

use App\Http\Controllers\MarkdownController;
use App\Services\Markdown\Http;
use App\Services\Markdown\Registry;
use App\Support\SiteIdentity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentDiscovery
{
    public function handle(Request $request, Closure $next): Response
    {
        $registry = app(Registry::class);
        $path = '/'.$request->path();
        $path = $path === '//' ? '/' : $path;
        $controller = app(MarkdownController::class);
        $read = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $file = ltrim($path, '/');
        if ($read && in_array($file, ['llms.txt', 'llms-full.txt', 'ai.txt', 'robots.txt'], true)) {
            $response = $this->machine($request, fn () => $controller->discovery($request, $file));
        } elseif ($read && $path === '/api/md/_catalog') {
            $response = $this->machine($request, fn () => $controller->catalog($request));
        } elseif ($read && ($path === '/api/md' || str_starts_with($path, '/api/md/'))) {
            $response = $controller->show($request, $path === '/api/md' ? '' : substr($path, 8));
        } elseif ($read && preg_match('~^/product/([^/]+)/document\.md$~', $path, $match)) {
            $response = $controller->show($request, '/product/'.$match[1]);
        } elseif ($read && $this->suffix($path, $registry)) {
            $target = substr($path, 0, -3);
            $response = $controller->show($request, $target === '/index' ? '' : $target);
        } elseif ($read && $registry->supports($path) && app(Http::class)->prefersMarkdown($request)) {
            $response = $controller->show($request, $path);
        } else {
            $response = $next($request);
            if ($registry->supports($path)) {
                $response->setVary('Accept', false);
            }
        }
        $links = [];
        foreach (['llms.txt', 'llms-full.txt', 'ai.txt'] as $name) {
            $links[] = '<'.app(SiteIdentity::class)->url('/'.$name).'>; rel="describedby"';
        }
        $existing = $response->headers->get('Link');
        $response->headers->set('Link', ($existing ? $existing.', ' : '').implode(', ', $links));

        return $response;
    }

    private function machine(Request $request, Closure $render): Response
    {
        try {
            return $render();
        } catch (\Throwable $error) {
            report($error);

            return app(Http::class)->response($request, [
                'canonical' => app(SiteIdentity::class)->url($request->getPathInfo()),
                'markdown' => "# Unable to render document\n\nPlease try again later.\n",
            ], 500);
        }
    }

    private function suffix(string $path, Registry $registry): bool
    {
        if (! str_ends_with($path, '.md') || preg_match('~^/(?:api|\.well-known|_next|storage|images|css|js|build|favicon)(?:/|$)~', $path)) {
            return false;
        }
        $file = realpath(public_path(ltrim($path, '/')));
        if ($file && str_starts_with($file, realpath(public_path()).DIRECTORY_SEPARATOR) && is_file($file)) {
            return false;
        }
        $route = $registry->match($path);
        if ($route && (str_ends_with($route->uri(), '.md') || str_contains($route->uri(), 'document.{format}'))) {
            return false;
        }

        return true;
    }
}
