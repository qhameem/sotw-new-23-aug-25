<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductLimitationResearchService
{
    private const SEARCH_ENDPOINT = 'https://html.duckduckgo.com/html/';
    private const SEARCH_TIMEOUT = 8;
    private const MAX_FINDINGS = 3;

    public function buildContext(string $productName, ?string $productUrl = null): string
    {
        $productName = trim($productName);

        if ($productName === '' || mb_strtolower($productName) === 'this product') {
            return '';
        }

        $blockedHost = $this->extractHost($productUrl);
        $findings = [];
        $seen = [];

        foreach ($this->buildQueries($productName) as $query) {
            foreach ($this->search($query, $blockedHost) as $finding) {
                $fingerprint = mb_strtolower(($finding['url'] ?? '') . '|' . ($finding['snippet'] ?? ''));

                if (isset($seen[$fingerprint])) {
                    continue;
                }

                $seen[$fingerprint] = true;
                $findings[] = $finding;

                if (count($findings) >= self::MAX_FINDINGS) {
                    break 2;
                }
            }
        }

        if ($findings === []) {
            return '';
        }

        $lines = ['Search-based limitation research:'];

        foreach ($findings as $finding) {
            $source = $finding['source'] !== '' ? $finding['source'] : 'Unknown source';
            $title = $finding['title'] !== '' ? $finding['title'] : 'Untitled result';
            $snippet = $finding['snippet'] !== '' ? $finding['snippet'] : 'No snippet available.';
            $lines[] = "- Source: {$source} | Title: {$title} | Snippet: {$snippet}";
        }

        return implode("\n", $lines);
    }

    private function buildQueries(string $productName): array
    {
        return [
            "\"{$productName}\" limitations",
            "\"{$productName}\" drawbacks OR cons OR tradeoffs",
        ];
    }

    private function search(string $query, ?string $blockedHost): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
            ])->timeout(self::SEARCH_TIMEOUT)->get(self::SEARCH_ENDPOINT, [
                'q' => $query,
            ]);

            if (!$response->successful()) {
                return [];
            }

            return $this->parseSearchResults($response->body(), $blockedHost);
        } catch (\Throwable $e) {
            Log::warning('Product limitation research search failed.', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function parseSearchResults(string $html, ?string $blockedHost): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (!$loaded) {
            return [];
        }

        $xpath = new DOMXPath($document);
        $resultNodes = $xpath->query('//div[contains(@class,"result")]');
        $results = [];

        foreach ($resultNodes as $resultNode) {
            $linkNode = $xpath->query('.//a[contains(@class,"result__a")]', $resultNode)->item(0);

            if (!$linkNode) {
                continue;
            }

            $title = $this->cleanText($linkNode->textContent);
            $url = $this->normalizeResultUrl((string) $linkNode->getAttribute('href'));
            $host = $this->extractHost($url);

            if ($url === '' || ($blockedHost !== null && $host === $blockedHost)) {
                continue;
            }

            $snippetNode = $xpath->query('.//*[contains(@class,"result__snippet")]', $resultNode)->item(0);
            $snippet = $this->cleanText($snippetNode?->textContent ?? '');

            if (!$this->looksLikeLimitationFinding($title . ' ' . $snippet)) {
                continue;
            }

            $results[] = [
                'title' => $title,
                'url' => $url,
                'source' => $host ?? '',
                'snippet' => $this->limitText($snippet, 220),
            ];

            if (count($results) >= self::MAX_FINDINGS) {
                break;
            }
        }

        return $results;
    }

    private function normalizeResultUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        $query = $parts['query'] ?? null;

        if (is_string($query)) {
            parse_str($query, $params);

            if (!empty($params['uddg']) && is_string($params['uddg'])) {
                return $params['uddg'];
            }
        }

        return $url;
    }

    private function looksLikeLimitationFinding(string $value): bool
    {
        $normalized = mb_strtolower($value);

        if ($normalized === '') {
            return false;
        }

        return (bool) preg_match('/\b(limitations?|drawbacks?|cons?|trade[- ]?offs?|downsides?|weaknesses|lacks?|missing|expensive|steep learning curve|only works|not ideal)\b/u', $normalized);
    }

    private function cleanText(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return trim($value);
    }

    private function limitText(string $value, int $maxLength): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $maxLength - 3)) . '...';
    }

    private function extractHost(?string $url): ?string
    {
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || trim($host) === '') {
            return null;
        }

        return mb_strtolower($host);
    }
}
