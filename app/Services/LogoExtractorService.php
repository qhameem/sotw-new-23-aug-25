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
                // Check if URL is valid and accessible
                $headers = get_headers($logo, 1);
                if (!$headers || (isset($headers[0]) && strpos($headers[0], '200') === false)) {
                    continue;
                }
                
                // Verify it's an image by checking content type or file extension
                $contentType = null;
                if (isset($headers['Content-Type'])) {
                    $contentType = is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type'];
                }
                
                $isImage = false;
                if ($contentType && strpos($contentType, 'image/') !== false) {
                    $isImage = true;
                } else {
                    // Fallback: check file extension
                    $pathInfo = pathinfo(parse_url($logo, PHP_URL_PATH));
                    $extension = strtolower($pathInfo['extension'] ?? '');
                    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'bmp']);
                }
                
                if (!$isImage) {
                    continue;
                }
                
                // Check image dimensions if possible
                $size = @getimagesize($logo);
                if ($size) {
                    // Accept images that are at least 100x100 (more lenient than 240x240)
                    // but prefer larger images
                    if ($size[0] >= 100 && $size[1] >= 100) {
                        $validLogos[] = $logo;
                    }
                } else {
                    // If we can't get dimensions, still include the image if it's accessible
                    $validLogos[] = $logo;
                }
            } catch (\Exception $e) {
                // Ignore images that can't be accessed or sized
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
            // Prioritize by size if we can determine it
            try {
                $size = @getimagesize($logo);
                if ($size && $size[0] >= 240 && $size[1] >= 240) $score += 2;
                if ($size && $size[0] >= 400 && $size[1] >= 400) $score += 1;
            } catch (\Exception $e) {
                // If we can't determine size, continue without penalty
            }
            
            $scoredLogos[] = ['url' => $logo, 'score' => $score];
        }

        usort($scoredLogos, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(array_column($scoredLogos, 'url'), 0, 4);
    }
}