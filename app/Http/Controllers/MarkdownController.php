<?php

namespace App\Http\Controllers;

use App\Services\Markdown\Discovery;
use App\Services\Markdown\Format;
use App\Services\Markdown\Http;
use App\Services\Markdown\Registry;
use App\Support\SiteIdentity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class MarkdownController extends Controller
{
    public function show(Request $request, string $path = ''): Response
    {
        $canonical = app(SiteIdentity::class)->url('/'.ltrim($path, '/'));
        try {
            $target = '/'.ltrim($path, '/').($request->getQueryString() ? '?'.$request->getQueryString() : '');
            $page = app(\App\Services\Markdown\CacheVersion::class)->remember(
                'page:'.$target,
                3600,
                fn () => app(Registry::class)->render($target)
            );

            return app(Http::class)->response($request, $page);
        } catch (\Throwable $e) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $status = $status === 404 ? 404 : 500;
            if ($status === 500) {
                report($e);
            }
            $page = app(Format::class)->page($status === 404 ? 'Not found' : 'Unable to render page', 'No public markdown document is available.', $canonical, '');

            return app(Http::class)->response($request, $page, $status);
        }
    }

    public function discovery(Request $request, string $file): Response
    {
        $discovery = app(Discovery::class);
        $body = match ($file) {
            'robots.txt' => $discovery->robots(),
            'ai.txt' => $discovery->policy(),
            default => $discovery->documents()[$file],
        };

        return app(Http::class)->response($request, ['canonical' => app(SiteIdentity::class)->url('/'.$file), 'markdown' => $body], 200, 'text/plain', 86400);
    }

    public function catalog(Request $request): Response
    {
        $offset = filter_var($request->query('offset', 0), FILTER_VALIDATE_INT);
        $limit = filter_var($request->query('limit', 50), FILTER_VALIDATE_INT);
        if ($offset === false || $offset < 0 || $limit === false || $limit < 1 || $limit > 100) {
            return app(Http::class)->response($request, ['canonical' => app(SiteIdentity::class)->url('/api/md/_catalog'), 'markdown' => "# Invalid catalog pagination\n"], 400);
        }
        $body = json_encode(app(Discovery::class)->catalog($offset, $limit), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return app(Http::class)->response($request, ['canonical' => app(SiteIdentity::class)->url('/api/md/_catalog'), 'markdown' => $body], 200, 'application/json');
    }
}
