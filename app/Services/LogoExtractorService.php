<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use DOMDocument;

class LogoExtractorService
{
    public function extract(string $url, string $html): array
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        $logos = [];

        // Method 1: Social Media Meta Tags
        $metas = $doc->getElementsByTagName('meta');
        foreach ($metas as $meta) {
            $property = strtolower($meta->getAttribute('property'));
            if ($property === 'og:image' || $property === 'twitter:image') {
                $logos[] = $this->resolveUrl($url, $meta->getAttribute('content'));
            }
        }

        // Method 2: Web App Manifest
        $links = $doc->getElementsByTagName('link');
        foreach ($links as $link) {
            if (strtolower($link->getAttribute('rel')) === 'manifest') {
                $manifestUrl = $this->resolveUrl($url, $link->getAttribute('href'));
                try {
                    $manifestContent = Http::get($manifestUrl)->json();
                    if (!empty($manifestContent['icons'])) {
                        foreach ($manifestContent['icons'] as $icon) {
                            $logos[] = $this->resolveUrl($url, $icon['src']);
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore errors fetching or parsing manifest
                }
            }
        }

        // Method 3: Searching for Logo in HTML
        $images = $doc->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (preg_match('/logo/i', $src)) {
                $logos[] = $this->resolveUrl($url, $src);
            }
        }
        
        // Method 4: High-Resolution Favicons
        foreach ($links as $link) {
            $rel = strtolower($link->getAttribute('rel'));
            if (in_array($rel, ['apple-touch-icon', 'apple-touch-icon-precomposed'])) {
                $logos[] = $this->resolveUrl($url, $link->getAttribute('href'));
            }
        }

        $logos = array_values(array_unique($logos));
        
        return $this->filterAndRankLogos($logos);
    }

    private function resolveUrl(string $baseUrl, string $relativeUrl): string
    {
        if (Str::startsWith($relativeUrl, ['http://', 'https://', '//'])) {
            if (Str::startsWith($relativeUrl, '//')) {
                return 'https:' . $relativeUrl;
            }
            return $relativeUrl;
        }

        $base = parse_url($baseUrl);
        $path = $base['path'] ?? '';

        if (Str::startsWith($relativeUrl, '/')) {
            $path = '';
        } else {
            $path = dirname($path);
        }
        
        $path = rtrim($path, '/');

        return $base['scheme'] . '://' . $base['host'] . $path . '/' . ltrim($relativeUrl, '/');
    }

    private function filterAndRankLogos(array $logos): array
    {
        $validLogos = [];
        foreach ($logos as $logo) {
            try {
                $headers = get_headers($logo, 1);
                if (strpos($headers[0], '200') === false) {
                    continue;
                }
                $size = getimagesize($logo);
                if ($size && $size[0] >= 240 && $size[1] >= 240) {
                    $validLogos[] = $logo;
                }
            } catch (\Exception $e) {
                // Ignore images that can't be accessed or sized
            }
        }

        $scoredLogos = [];
        foreach ($validLogos as $logo) {
            $score = 0;
            if (stripos($logo, 'logo') !== false) $score += 5;
            if (stripos($logo, 'og') !== false) $score += 4;
            if (stripos($logo, 'twitter') !== false) $score += 4;
            if (stripos($logo, '.svg') !== false) $score += 3;
            if (stripos($logo, '.png') !== false) $score += 2;
            $scoredLogos[] = ['url' => $logo, 'score' => $score];
        }

        usort($scoredLogos, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scoredLogos, 'url'), 0, 3);
    }
}