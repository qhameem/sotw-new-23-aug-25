<?php

namespace App\Services;

class NameExtractorService
{
    public function extractFromHtml(string $html, string $url = ''): string
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        foreach ($xpath->query('//meta[translate(@property,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="og:site_name"]') ?: [] as $meta) {
            $name = trim($meta->getAttribute('content'));
            if ($name !== '') {
                return $name;
            }
        }
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $script) {
            $data = json_decode($script->textContent, true);
            foreach ($this->jsonLdNodes($data) as $node) {
                $types = (array) ($node['@type'] ?? []);
                if (array_intersect($types, ['Organization', 'SoftwareApplication', 'WebSite']) && filled($node['name'] ?? null)) {
                    return trim((string) $node['name']);
                }
            }
        }
        $title = trim($document->getElementsByTagName('title')->item(0)?->textContent ?? '');

        return $this->extract($title, $url);
    }

    private function jsonLdNodes(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }
        $nodes = [];
        foreach (array_merge([$data], $data['@graph'] ?? [], array_is_list($data) ? $data : []) as $node) {
            if (is_array($node)) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }
    /**
     * Extracts the most likely product name from a page title.
     *
     * @param string $title The full title from the page's <title> tag.
     * @return string The extracted name.
     */
    public function extract(string $title, string $url = ''): string
    {
        if (trim($title) === '') {
            return $this->extractFromUrl($url);
        }

        // 1. Initial cleaning: Remove common trailing noise like " - Home", " | Official Site"
        $title = preg_replace('/\b(Home|Official Site|Login|Sign Up|Register|Landing Page|Website)\b/i', '', $title);

        // 2. Split by common major separators EXCEPT single hyphen (which might be part of a name like AI-Powered)
        // We only split by hyphen if it has spaces around it: "Product - Tagline"
        $parts = preg_split('/[|–—]| \- /u', $title);
        $parts = array_filter(array_map('trim', $parts));

        if (empty($parts)) {
            return $title;
        }

        // 3. Brand name is ALMOST ALWAYS the first part in modern titles (e.g. "Brand | Tagline")
        // or the last part (e.g. "Tagline - Brand"). We'll score them.
        $candidates = [];
        foreach ($parts as $index => $part) {
            $score = 100;

            // First part gets a big bonus
            if ($index === 0)
                $score += 50;
            // Last part gets a small bonus
            if ($index === count($parts) - 1 && $index > 0)
                $score += 20;

            // Penalty for generic/tool words
            if (preg_match('/\b(AI|Product|Tool|App|Software|Best|Waitlist)\b/i', $part))
                $score -= 30;

            // Penalty for being TOO short (1-2 chars) unless it's the only option
            if (strlen($part) <= 2)
                $score -= 40;

            // Penalty for being TOO long (likely a sentence/tagline)
            if (strlen($part) > 25)
                $score -= 20;

            $candidates[] = ['text' => $part, 'score' => $score];
        }

        // Sort by score descending
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        $best = $candidates[0]['text'];
        $urlCandidate = $this->extractFromUrl($url);

        if ($this->looksLikeTagline($best) && $urlCandidate !== '') {
            return $urlCandidate;
        }

        return $best;
    }

    private function looksLikeTagline(string $candidate): bool
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return true;
        }

        return str_word_count($candidate) > 5 || strlen($candidate) > 40;
    }

    private function extractFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || trim($host) === '') {
            return '';
        }

        $host = preg_replace('/^www\./i', '', $host) ?? $host;
        $label = explode('.', $host)[0] ?? '';
        $label = trim($label);

        if ($label === '') {
            return '';
        }

        return implode(' ', array_map(static function (string $part) {
            return ucfirst(strtolower($part));
        }, array_filter(explode('-', $label))));
    }
}
