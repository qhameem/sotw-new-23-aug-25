<?php

namespace App\Services;

use App\Support\PublicUrlGuard;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class ProductSourceCollector
{
    private const PATHS = ['pricing', 'faq', 'download', 'docs', 'about'];

    public function collect(string $url, string $html, string $additional = ''): string
    {
        $blocks = [$this->textBlock($url, $html)];
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $origin = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
        $links = [];
        foreach ($xpath->query('//a[@href]') ?: [] as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            $target = filter_var($href, FILTER_VALIDATE_URL) ? $href : rtrim($origin, '/').'/'.ltrim($href, '/');
            $path = strtolower((string) parse_url($target, PHP_URL_PATH));
            foreach (self::PATHS as $kind) {
                if (! isset($links[$kind]) && preg_match('~(?:^|/)(?:'.$kind.')(?:/|$)~', $path)) {
                    $links[$kind] = $target;
                }
            }
        }
        foreach (self::PATHS as $kind) {
            foreach (array_unique(array_filter([$links[$kind] ?? null, rtrim($origin, '/').'/'.$kind])) as $candidate) {
                try {
                    $candidate = PublicUrlGuard::sanitizePublicHttpUrl($candidate);
                    if (parse_url($candidate, PHP_URL_HOST) !== parse_url($url, PHP_URL_HOST)) {
                        continue;
                    }
                    $response = Http::timeout(4)->withOptions(['allow_redirects' => false, 'stream' => true])->get($candidate);
                    $body = $response->toPsrResponse()->getBody()->read(262145);
                    if ($response->successful() && strlen($body) <= 262144 && str_contains(strtolower((string) $response->header('Content-Type')), 'text/html')) {
                        $blocks[] = $this->textBlock($candidate, $body);
                        break;
                    }
                } catch (\Throwable) {
                    // An optional page must not stop extraction.
                }
            }
        }
        if (trim($additional) !== '') {
            $blocks[] = "Source: Additional resources\n".mb_substr(strip_tags($additional), 0, 2000);
        }

        return mb_substr(implode("\n\n", $blocks), 0, 24000);
    }

    private function textBlock(string $url, string $html): string
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//script|//style|//nav|//footer|//aside|//form') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }
        $text = preg_replace('/\s+/u', ' ', $document->textContent ?? '') ?? '';
        $headings = [];
        foreach ($xpath->query('//h1|//h2|//h3') ?: [] as $heading) {
            $headings[] = strtoupper($heading->nodeName).': '.trim($heading->textContent);
        }
        $links = [];
        foreach ($xpath->query('//a[@href]') ?: [] as $anchor) {
            $links[] = $anchor->getAttribute('href');
        }

        return 'Source: '.$url."\n".mb_substr(implode("\n", array_slice($headings, 0, 12)), 0, 400)."\n".mb_substr(trim($text), 0, 2700)."\nLinks: ".mb_substr(implode(' ', $links), 0, 350);
    }
}
