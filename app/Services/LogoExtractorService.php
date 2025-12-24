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
                    $manifestContent = Http::timeout(5)->get($manifestUrl)->json();
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
            $alt = $img->getAttribute('alt');
            $class = $img->getAttribute('class');
            $id = $img->getAttribute('id');
            
            // Check for logo in various attributes
            if (preg_match('/logo/i', $src) ||
                preg_match('/logo/i', $alt) ||
                preg_match('/logo/i', $class) ||
                preg_match('/logo/i', $id)) {
                $logos[] = $this->resolveUrl($url, $src);
            }
        }
        
        // Method 4: Additional logo patterns in img tags
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $class = $img->getAttribute('class');
            $id = $img->getAttribute('id');
            
            // Look for common logo-related class names and IDs
            if (preg_match('/(brand|logo|icon)/i', $class) ||
                preg_match('/(brand|logo|icon)/i', $id)) {
                $logos[] = $this->resolveUrl($url, $src);
            }
        }
        
        // Method 5: High-Resolution Favicons and Touch Icons
        foreach ($links as $link) {
            $rel = strtolower($link->getAttribute('rel'));
            if (in_array($rel, ['apple-touch-icon', 'apple-touch-icon-precomposed', 'icon', 'shortcut icon'])) {
                $logos[] = $this->resolveUrl($url, $link->getAttribute('href'));
            }
        }
        
        // Method 6: Additional logo patterns in link tags
        foreach ($links as $link) {
            $rel = strtolower($link->getAttribute('rel'));
            $href = $link->getAttribute('href');
            
            if (preg_match('/logo/i', $href) && in_array($rel, ['preload', 'prefetch'])) {
                $logos[] = $this->resolveUrl($url, $href);
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
                // Verify it's an image by checking file extension only (faster approach)
                $pathInfo = pathinfo(parse_url($logo, PHP_URL_PATH));
                $extension = strtolower($pathInfo['extension'] ?? '');
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'bmp']);
                
                if (!$isImage) {
                    continue;
                }
                
                // Just add the logo if it has a valid image extension
                $validLogos[] = $logo;
            } catch (\Exception $e) {
                // Ignore images that can't be processed
            }
        }

        $scoredLogos = [];
        foreach ($validLogos as $logo) {
            $score = 0;
            // Prioritize by filename patterns
            if (stripos($logo, 'logo') !== false) $score += 5;
            if (stripos($logo, 'og') !== false) $score += 4;
            if (stripos($logo, 'twitter') !== false) $score += 4;
            if (stripos($logo, 'icon') !== false) $score += 3;
            if (stripos($logo, 'brand') !== false) $score += 3;
            // Prioritize by file format
            if (stripos($logo, '.svg') !== false) $score += 3;
            if (stripos($logo, '.png') !== false) $score += 2;
            if (stripos($logo, '.jpg') !== false || stripos($logo, '.jpeg') !== false) $score += 1;
            // Skip size checking to avoid hanging on getimagesize() calls
            
            $scoredLogos[] = ['url' => $logo, 'score' => $score];
        }

        usort($scoredLogos, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scoredLogos, 'url'), 0, 4);
    }
}