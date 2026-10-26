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
        $report = \App\Support\ExtractionReport::current();
        $report->record('source collection', 'Converting homepage and supporting pages to cleaned Markdown.');
        $markdown = app(ProductPageMarkdown::class);
        $pages = [['url' => $url, 'markdown' => $markdown->convert($html, $url)]];
        $firecrawl = app(FirecrawlService::class);
        $fallbackAttempts = 0;
        $fallback = function (string $target) use ($firecrawl, &$fallbackAttempts, $report): ?array {
            if ($fallbackAttempts >= 2) {
                return null;
            }
            $fallbackAttempts++;
            $result = $firecrawl->scrape($target);
            if ($result) {
                $report->record('source collection', 'Firecrawl collected '.$target.' at '.$result['retrieved_at'], 'success');
            }

            return $result;
        };
        if ($firecrawl->insufficient($pages[0]['markdown'])) {
            $result = $fallback($url);
            if ($result) {
                $pages[0]['markdown'] = $result['markdown'];
                $html = $result['html'] ?: $html;
            }
        }
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $origin = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
        $links = [];
        foreach ($xpath->query('//a[@href]') ?: [] as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            $target = $markdown->resolveUrl($url, $href);
            if ($target === null) {
                continue;
            }
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
                    $report->record('source collection', 'Reading '.$candidate);
                    $response = Http::timeout(4)->withOptions(['allow_redirects' => false, 'stream' => true])->get($candidate);
                    $body = $response->toPsrResponse()->getBody()->read(262145);
                    if ($response->successful() && strlen($body) <= 262144 && str_contains(strtolower((string) $response->header('Content-Type')), 'text/html')) {
                        $report->record('source collection', $kind.' page collected.', 'success');
                        $content = $markdown->convert($body, $candidate);
                        if ($firecrawl->insufficient($content)) {
                            $content = $fallback($candidate)['markdown'] ?? $content;
                        }
                        $pages[] = ['url' => $candidate, 'markdown' => $content];
                        break;
                    }
                    if (in_array($response->status(), [403, 429, 500, 502, 503], true)) {
                        $result = $fallback($candidate);
                        if ($result) {
                            $pages[] = ['url' => $candidate, 'markdown' => $result['markdown']];
                            break;
                        }
                    }
                    $report->record('source collection', $kind.' skipped: HTTP '.$response->status().', non-HTML content, or page exceeds size limit.', 'warning');
                } catch (\Throwable) {
                    $report->record('source collection', $kind.' unavailable or URL rejected; continuing.', 'warning');
                    $result = $fallback($candidate);
                    if ($result) {
                        $pages[] = ['url' => $candidate, 'markdown' => $result['markdown']];
                        break;
                    }
                }
            }
        }
        if (trim($additional) !== '') {
            $pages[] = ['url' => 'Additional resources', 'markdown' => $markdown->convert($additional, $url)];
        }
        $budget = 24000;
        $headers = array_sum(array_map(fn ($page) => mb_strlen('Source: '.$page['url']."\n") + 2, $pages));
        $pageBudget = max(1, (int) floor(($budget - $headers) / count($pages)));
        $blocks = [];
        foreach ($pages as $page) {
            $content = $markdown->limit($page['markdown'], $pageBudget);
            if (mb_strlen($content) < mb_strlen($page['markdown'])) {
                $report->record('source collection', $page['url'].': Markdown shortened by section priority to '.$pageBudget.' characters.');
            }
            $blocks[] = 'Source: '.$page['url']."\n".$content;
        }
        $report->record('source collection', count($blocks).' Markdown source blocks collected; context limited to '.$budget.' characters.', 'success');

        return mb_substr(implode("\n\n", $blocks), 0, $budget);
    }
}
