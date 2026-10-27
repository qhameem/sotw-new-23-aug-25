<?php

namespace App\Services\Markdown;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Http
{
    public function prefersMarkdown(Request $request): bool
    {
        $accept = $request->headers->get('Accept', '');
        if (trim($accept) === '') {
            return false;
        }
        $accept = preg_replace_callback('/(?:^|,)\s*([^;,]+)/', fn ($match) => str_replace($match[1], strtolower($match[1]), $match[0]), $accept);
        $items = \Symfony\Component\HttpFoundation\AcceptHeader::fromString($accept);
        $markdown = $items->get('text/markdown');
        if (! $markdown || $markdown->getQuality() <= 0) {
            return false;
        }
        $html = $items->get('text/html') ?? $items->get('text/*') ?? $items->get('*/*');

        return $markdown->getQuality() > ($html?->getQuality() ?? 0);
    }

    public function response(Request $request, array $page, int $status = 200, string $type = 'text/markdown', int $ttl = 3600): Response
    {
        $body = $page['markdown'];
        $headers = [
            'Content-Type' => $type.'; charset=utf-8',
            'X-Markdown-Tokens' => (string) (int) ceil(mb_strlen($body) / 4),
            'X-Robots-Tag' => 'noindex, follow',
            'Content-Location' => $page['canonical'],
            'Link' => '<'.$page['canonical'].'>; rel="canonical"',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Accept, Content-Type',
            'Cache-Control' => $status === 200
                ? "public, s-maxage={$ttl}, stale-while-revalidate=".($ttl === 86400 ? '604800' : '86400')
                : 'no-store',
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if (! empty($page['date'])) {
            $headers['Last-Modified'] = \Illuminate\Support\Carbon::parse($page['date'], 'UTC')->toRfc7231String();
        }

        return response($request->isMethod('HEAD') || $request->isMethod('OPTIONS') ? '' : $body, $request->isMethod('OPTIONS') ? 204 : $status, $headers);
    }
}
