<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductDiscoverySource;
use App\Models\ProductRecommendation;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProductDiscoveryService
{
    public function inspect(string $url): array
    {
        $this->guardPublicUrl($url);
        $response = Http::accept('text/html, application/rss+xml, application/atom+xml, application/xml')
            ->withUserAgent('SoftwareOnTheWeb Product Discovery Bot/1.0')
            ->connectTimeout(10)->timeout(25)->retry(2, 500)->get($url);
        $response->throw();

        $body = $response->body();
        $contentType = strtolower($response->header('content-type') ?? '');
        $isFeed = str_contains($contentType, 'xml') || preg_match('/^\s*<(rss|feed)/i', $body) === 1;

        if ($isFeed) {
            $document = $this->document($body, true);
            $xpath = new DOMXPath($document);

            return [
                'name' => $this->xpathText($xpath, '/*[local-name()="rss"]/*[local-name()="channel"]/*[local-name()="title"][1] | /*[local-name()="feed"]/*[local-name()="title"][1]', $document) ?: $this->hostName($url),
                'url' => $url,
                'type' => 'feed',
                'item_selector' => null,
                'link_selector' => null,
                'title_selector' => null,
                'description_selector' => null,
            ];
        }

        $document = $this->document($body);
        $xpath = new DOMXPath($document);
        $feedNode = $xpath->query('//link[contains(translate(@type, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "rss") or contains(translate(@type, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "atom")][@href]')?->item(0);
        $feedUrl = $feedNode instanceof DOMElement ? $this->absoluteUrl($feedNode->getAttribute('href'), $url) : null;
        $title = trim((string) $xpath->query('//meta[@property="og:site_name"]/@content | //title[1]')?->item(0)?->nodeValue);
        $hasArticles = ($xpath->query('//article')?->length ?? 0) > 0;

        return [
            'name' => Str::limit(preg_replace('/\s+[|–—-].*$/u', '', $title) ?: $this->hostName($url), 255, ''),
            'url' => $feedUrl ?: $url,
            'type' => $feedUrl ? 'feed' : 'html',
            'item_selector' => $feedUrl ? null : ($hasArticles ? 'article' : null),
            'link_selector' => $feedUrl ? null : ($hasArticles ? 'a' : null),
            'title_selector' => $feedUrl ? null : (($xpath->query('//article//h2')?->length ?? 0) > 0 ? 'h2' : (($xpath->query('//article//h3')?->length ?? 0) > 0 ? 'h3' : null)),
            'description_selector' => $feedUrl ? null : (($xpath->query('//article//p')?->length ?? 0) > 0 ? 'p' : null),
        ];
    }

    public function scan(ProductDiscoverySource $source): int
    {
        $this->guardPublicUrl($source->url);
        $source->forceFill(['last_scanned_at' => now(), 'last_error' => null])->save();

        try {
            $response = Http::accept('text/html, application/rss+xml, application/atom+xml, application/xml')
                ->withUserAgent('SoftwareOnTheWeb Product Discovery Bot/1.0')
                ->connectTimeout(10)->timeout(25)->retry(2, 500)
                ->get($source->url);
            $response->throw();

            $items = $this->extract($source, $response);
            $stored = $this->store($source, array_slice($items, 0, $source->max_items));
            $source->forceFill(['last_success_at' => now(), 'last_error' => null])->save();

            return $stored;
        } catch (\Throwable $exception) {
            $source->forceFill(['last_error' => Str::limit($exception->getMessage(), 1000)])->save();
            throw $exception;
        }
    }

    private function extract(ProductDiscoverySource $source, Response $response): array
    {
        $contentType = strtolower($response->header('content-type') ?? '');
        $body = $response->body();
        $type = $source->type === 'auto'
            ? ((str_contains($contentType, 'xml') || preg_match('/^\s*<(rss|feed)/i', $body)) ? 'feed' : 'html')
            : $source->type;

        return $type === 'feed' ? $this->extractFeed($body, $source->url) : $this->extractHtml($body, $source);
    }

    private function extractFeed(string $body, string $baseUrl): array
    {
        $document = $this->document($body, true);
        $xpath = new DOMXPath($document);
        $items = [];

        foreach ($xpath->query('//*[local-name()="item" or local-name()="entry"]') ?: [] as $node) {
            $title = $this->xpathText($xpath, './*[local-name()="title"][1]', $node);
            $url = $this->xpathText($xpath, './*[local-name()="link"][1]', $node);
            if ($url === '' && $node instanceof DOMElement) {
                $link = $xpath->query('./*[local-name()="link"][1]', $node)?->item(0);
                $url = $link instanceof DOMElement ? $link->getAttribute('href') : '';
            }
            $description = $this->xpathText($xpath, './*[local-name()="description" or local-name()="summary" or local-name()="content"][1]', $node);
            $items[] = compact('title', 'url', 'description');
        }

        return $this->cleanItems($items, $baseUrl);
    }

    private function extractHtml(string $body, ProductDiscoverySource $source): array
    {
        $document = $this->document($body);
        $xpath = new DOMXPath($document);
        $items = [];

        if (filled($source->item_selector)) {
            foreach ($xpath->query($this->selectorToXpath($source->item_selector)) ?: [] as $node) {
                $linkNode = $this->firstNode($xpath, $source->link_selector ?: 'a', $node);
                if (! $linkNode instanceof DOMElement) {
                    continue;
                }
                $titleNode = $this->firstNode($xpath, $source->title_selector ?: $source->link_selector ?: 'a', $node);
                $descriptionNode = filled($source->description_selector)
                    ? $this->firstNode($xpath, $source->description_selector, $node) : null;
                $items[] = [
                    'title' => $titleNode?->textContent ?? $linkNode->textContent,
                    'url' => $linkNode->getAttribute('href'),
                    'description' => $descriptionNode?->textContent,
                ];
            }
        } else {
            foreach ($xpath->query('//main//a[@href] | //article//a[@href]') ?: [] as $linkNode) {
                if ($linkNode instanceof DOMElement) {
                    $items[] = ['title' => $linkNode->textContent, 'url' => $linkNode->getAttribute('href'), 'description' => null];
                }
            }
        }

        return $this->cleanItems($items, $source->url);
    }

    private function store(ProductDiscoverySource $source, array $items): int
    {
        $stored = 0;
        foreach ($items as $item) {
            if ($this->alreadyPublished($item['url'])) {
                continue;
            }

            $hash = hash('sha256', Product::normalizeLink($item['url']) ?: $item['url']);
            ProductRecommendation::updateOrCreate(
                ['url_hash' => $hash],
                [
                    'source_id' => $source->id,
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'description' => $item['description'],
                    'score' => $this->score($item),
                    'last_seen_at' => now(),
                    'discovered_at' => ProductRecommendation::where('url_hash', $hash)->value('discovered_at') ?: now(),
                ]
            );
            $stored++;
        }

        return $stored;
    }

    private function cleanItems(array $items, string $baseUrl): array
    {
        $clean = [];
        foreach ($items as $item) {
            $title = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($item['title'] ?? ''))) ?? '');
            $url = $this->absoluteUrl(trim((string) ($item['url'] ?? '')), $baseUrl);
            if (mb_strlen($title) < 2 || ! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                continue;
            }
            $description = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($item['description'] ?? ''))) ?? '');
            $clean[hash('sha256', $url)] = [
                'title' => Str::limit($title, 255, ''),
                'url' => $url,
                'description' => $description === '' ? null : Str::limit($description, 1000),
            ];
        }

        return array_values($clean);
    }

    private function score(array $item): int
    {
        $score = 45;
        if (filled($item['description'])) {
            $score += 15;
        }
        if (preg_match('/\b(launch|new|introducing|ai|saas|app|tool|platform)\b/i', $item['title'].' '.($item['description'] ?? ''))) {
            $score += 15;
        }
        if (mb_strlen($item['title']) >= 5 && mb_strlen($item['title']) <= 80) {
            $score += 10;
        }

        return min(100, $score);
    }

    private function alreadyPublished(string $url): bool
    {
        $candidates = Product::equivalentLinkCandidates($url);

        return $candidates !== [] && Product::whereIn('link', $candidates)->exists();
    }

    private function guardPublicUrl(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '' || in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
            throw new InvalidArgumentException('A public HTTP or HTTPS URL is required.');
        }
        if (! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only HTTP and HTTPS sources are supported.');
        }
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        foreach ($records ?: [] as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new InvalidArgumentException('Private or reserved network sources are not allowed.');
            }
        }
    }

    private function document(string $body, bool $xml = false): DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $xml
            ? $document->loadXML($body, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET)
            : $document->loadHTML($body, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new RuntimeException('The source response could not be parsed.');
        }

        return $document;
    }

    private function firstNode(DOMXPath $xpath, string $selector, DOMNode $context): ?DOMNode
    {
        return $xpath->query('.'.$this->selectorToXpath($selector), $context)?->item(0);
    }

    private function selectorToXpath(string $selector): string
    {
        $selector = trim($selector);
        if (str_starts_with($selector, '//')) {
            return $selector;
        }
        if (preg_match('/^[.#]?[A-Za-z0-9_-]+$/', $selector) !== 1) {
            throw new InvalidArgumentException('Selectors support tag, .class, #id, or XPath beginning with //.');
        }
        if ($selector[0] === '.') {
            return '//*[contains(concat(" ", normalize-space(@class), " "), " '.substr($selector, 1).' ")]';
        }
        if ($selector[0] === '#') {
            return '//*[@id="'.substr($selector, 1).'"]';
        }

        return '//'.$selector;
    }

    private function xpathText(DOMXPath $xpath, string $query, DOMNode $node): string
    {
        return trim((string) $xpath->query($query, $node)?->item(0)?->textContent);
    }

    private function absoluteUrl(string $url, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return (parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https').':'.$url;
        }
        if ($url === '' || str_starts_with($url, '#') || preg_match('#^(mailto|javascript|tel):#i', $url)) {
            return '';
        }
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $port = parse_url($baseUrl, PHP_URL_PORT);
        $origin = $scheme.'://'.$host.($port ? ':'.$port : '');

        if (str_starts_with($url, '/')) {
            return $origin.$url;
        }

        $path = (string) parse_url($baseUrl, PHP_URL_PATH);
        $directory = str_ends_with($path, '/')
            ? rtrim($path, '/')
            : rtrim(str_replace('\\', '/', dirname($path)), '/');

        return $origin.($directory === '' || $directory === '.' ? '' : $directory).'/'.$url;
    }

    private function hostName(string $url): string
    {
        $host = preg_replace('/^www\./i', '', (string) parse_url($url, PHP_URL_HOST));

        return Str::headline(explode('.', $host)[0] ?? $host);
    }
}
