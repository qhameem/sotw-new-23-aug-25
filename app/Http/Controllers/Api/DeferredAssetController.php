<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CodeSnippet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DeferredAssetController extends Controller
{
    public function __invoke(Request $request)
    {
        $pageRequest = $this->buildPageRequest($request);

        return response()->json([
            'ga_code' => $this->resolveGoogleAnalyticsCode(),
            'head_snippets' => $this->resolveSnippets('head', $pageRequest),
            'body_snippets' => $this->resolveSnippets('body', $pageRequest),
            'sidebar_snippets' => $this->resolveSnippets('sidebar', $pageRequest),
        ]);
    }

    protected function buildPageRequest(Request $request): Request
    {
        $path = '/' . ltrim((string) $request->query('path', '/'), '/');
        $routeName = trim((string) $request->query('route_name', ''));

        $pageRequest = Request::create(
            $path,
            'GET',
            [],
            $request->cookies->all(),
            [],
            $request->server->all()
        );

        $pageRequest->headers->replace($request->headers->all());
        $pageRequest->setUserResolver($request->getUserResolver());

        if ($routeName !== '') {
            $pageRequest->setRouteResolver(function () use ($path, $routeName) {
                return tap(new \Illuminate\Routing\Route(['GET'], $path, []), function ($route) use ($routeName) {
                    $route->name($routeName);
                });
            });
        }

        return $pageRequest;
    }

    protected function resolveGoogleAnalyticsCode(): string
    {
        if (Auth::check() || !Storage::disk('local')->exists('settings.json')) {
            return '';
        }

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);

        return (string) ($settings['google_analytics_code'] ?? '');
    }

    protected function resolveSnippets(string $location, Request $request): array
    {
        return CodeSnippet::query()
            ->where('location', $location)
            ->get()
            ->filter(fn (CodeSnippet $snippet) => $snippet->shouldRenderFor($request))
            ->map(fn (CodeSnippet $snippet) => html_entity_decode($snippet->code))
            ->values()
            ->all();
    }
}
