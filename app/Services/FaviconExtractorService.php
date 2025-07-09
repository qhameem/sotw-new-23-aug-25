<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler; // For parsing HTML
use Illuminate\Support\Str; // For string manipulations
use Exception;

class FaviconExtractorService
{
    protected int $timeoutSeconds = 5; // Default timeout for HTTP requests
    protected array $preferredFormats = ['svg', 'png', 'webp', 'ico', 'jpg', 'jpeg', 'gif'];

    public function __construct(int $timeoutSeconds = 5)
    {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * Extracts the best quality favicon URL from a given domain URL.
     *
     * @param string $url The URL of the website.
     * @return string|null The URL of the best favicon, or null if not found/error.
     */
    public function extract(string $url): ?string // Corrected function name
    {
        $icons = [];
        $baseUrl = $this->getBaseUrl($url);
        if (!$baseUrl) {
            Log::error("FaviconExtractor: Could not determine base URL for {$url}");
            return null;
        }

        try {
            $response = Http::timeout($this->timeoutSeconds)->get($url);
            if (!$response->successful()) {
                Log::warning("FaviconExtractor: Failed to fetch main page {$url}. Status: " . $response->status());
                // Try fetching favicon.ico as a fallback even if main page fails
                return $this->getFallbackFaviconIco($baseUrl);
            }
            $htmlContent = $response->body();
            $crawler = new Crawler($htmlContent, $url); // Pass URL as second arg to resolve relative links

            // 1. Parse <link> tags
            $crawler->filter('link[rel*="icon"], link[rel*="apple-touch-icon"]')->each(function (Crawler $node) use (&$icons, $baseUrl) {
                $href = $node->attr('href');
                if ($href) {
                    $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                    $sizes = $node->attr('sizes'); // e.g., "16x16", "32x32 64x64", "any"
                    $type = $node->attr('type'); // e.g., "image/png", "image/svg+xml"
                    
                    $icons[] = [
                        'url' => $absoluteUrl,
                        'sizes' => $sizes,
                        'type' => $type,
                        'source' => 'link_tag',
                        'rel' => $node->attr('rel')
                    ];
                }
            });

            // 2. Check for Web App Manifest (manifest.json)
            $manifestUrl = null;
            $crawler->filter('link[rel="manifest"]')->each(function (Crawler $node) use (&$manifestUrl, $baseUrl) {
                $href = $node->attr('href');
                if ($href) {
                    $manifestUrl = $this->makeAbsoluteUrl($href, $baseUrl);
                }
            });

            if ($manifestUrl) {
                try {
                    $manifestResponse = Http::timeout($this->timeoutSeconds)->get($manifestUrl);
                    if ($manifestResponse->successful()) {
                        $manifestData = $manifestResponse->json();
                        if (isset($manifestData['icons']) && is_array($manifestData['icons'])) {
                            foreach ($manifestData['icons'] as $iconData) {
                                if (isset($iconData['src'])) {
                                    $icons[] = [
                                        'url' => $this->makeAbsoluteUrl($iconData['src'], $this->getBaseUrl($manifestUrl) ?: $baseUrl),
                                        'sizes' => $iconData['sizes'] ?? null,
                                        'type' => $iconData['type'] ?? null,
                                        'source' => 'manifest'
                                    ];
                                }
                            }
                        }
                    } else {
                        Log::warning("FaviconExtractor: Failed to fetch manifest {$manifestUrl}. Status: " . $manifestResponse->status());
                    }
                } catch (Exception $e) {
                    Log::error("FaviconExtractor: Error fetching or parsing manifest {$manifestUrl}: " . $e->getMessage());
                }
            }

        } catch (Exception $e) {
            Log::error("FaviconExtractor: Error fetching main page {$url}: " . $e->getMessage());
            // Fallback to favicon.ico if main page fetch fails catastrophically
            return $this->getFallbackFaviconIco($baseUrl);
        }
        
        // 3. Add /favicon.ico as a candidate (will be processed with others)
        $icons[] = [
            'url' => $this->makeAbsoluteUrl('/favicon.ico', $baseUrl),
            'sizes' => null, // We'll determine this if it exists
            'type' => 'image/x-icon', // Common type for .ico
            'source' => 'default_ico'
        ];


        // Process icons to find the best one
        return $this->findBestIcon($icons);
    }

    protected function getBaseUrl(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
            $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
            return $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $port;
        }
        return null;
    }

    protected function makeAbsoluteUrl(string $href, string $baseUrl): string
    {
        if (Str::startsWith($href, ['http://', 'https://', '//'])) {
            if (Str::startsWith($href, '//')) {
                return parse_url($baseUrl, PHP_URL_SCHEME) . ':' . $href;
            }
            return $href;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }
    
    protected function getFallbackFaviconIco(string $baseUrl): ?string
    {
        $faviconIcoUrl = $this->makeAbsoluteUrl('/favicon.ico', $baseUrl);
        try {
            $response = Http::timeout($this->timeoutSeconds / 2)->head($faviconIcoUrl); // Quicker HEAD request
            if ($response->successful()) {
                Log::info("FaviconExtractor: Found fallback /favicon.ico at {$faviconIcoUrl}");
                return $faviconIcoUrl;
            }
        } catch (Exception $e) {
            Log::warning("FaviconExtractor: Error checking fallback /favicon.ico at {$faviconIcoUrl}: " . $e->getMessage());
        }
        return null;
    }


    protected function findBestIcon(array $icons): ?string
    {
        $validIcons = [];
        $processedUrls = []; // To avoid processing the same URL multiple times

        foreach ($icons as $icon) {
            if (empty($icon['url']) || in_array($icon['url'], $processedUrls)) {
                continue;
            }
            $processedUrls[] = $icon['url'];

            Log::info("FaviconExtractor: Processing candidate icon: {$icon['url']} (Source: {$icon['source']})");

            try {
                // For SVGs, we might not get dimensions easily with getimagesize.
                // We can prioritize them if found and assume they are scalable.
                $fileExtension = strtolower(pathinfo(parse_url($icon['url'], PHP_URL_PATH), PATHINFO_EXTENSION));
                if ($fileExtension === 'svg' || (isset($icon['type']) && Str::contains($icon['type'], 'svg'))) {
                    // Check if SVG actually exists and is accessible
                    $headResponse = Http::timeout($this->timeoutSeconds / 2)->head($icon['url']);
                    if ($headResponse->successful()) {
                        Log::info("FaviconExtractor: Found SVG icon: {$icon['url']}");
                        $validIcons[] = ['url' => $icon['url'], 'width' => 99999, 'height' => 99999, 'format' => 'svg']; // Prioritize SVG
                        continue; // Consider SVG the best if found
                    } else {
                         Log::warning("FaviconExtractor: SVG icon {$icon['url']} not accessible. Status: " . $headResponse->status());
                         continue;
                    }
                }

                // For raster images, try to get dimensions
                // First, try to parse from 'sizes' attribute if available and not 'any'
                $dimensions = $this->parseSizesAttribute($icon['sizes'] ?? '');
                
                if ($dimensions) {
                     Log::info("FaviconExtractor: Parsed dimensions from 'sizes' for {$icon['url']}: {$dimensions['width']}x{$dimensions['height']}");
                     $validIcons[] = ['url' => $icon['url'], 'width' => $dimensions['width'], 'height' => $dimensions['height'], 'format' => $fileExtension ?: 'unknown'];
                } else {
                    // If 'sizes' not helpful, download and inspect (be cautious with large files)
                    // We can do a HEAD request first to check Content-Type and Content-Length
                    $headResponse = Http::timeout($this->timeoutSeconds / 2)->head($icon['url']);
                    if (!$headResponse->successful() || Str::contains($headResponse->header('Content-Type'), ['text/html', 'application/xml'])) {
                        Log::warning("FaviconExtractor: Icon {$icon['url']} not accessible or not an image. Status: " . $headResponse->status() . " Type: " . $headResponse->header('Content-Type'));
                        continue;
                    }
                    
                    // Small optimization: if it's favicon.ico, getimagesize might work directly on URL
                    if ($icon['source'] === 'default_ico' || $fileExtension === 'ico') {
                         $imageSize = @getimagesize($icon['url']);
                         if ($imageSize && $imageSize[0] > 0 && $imageSize[1] > 0) {
                            Log::info("FaviconExtractor: Got dimensions for .ico {$icon['url']} via getimagesize: {$imageSize[0]}x{$imageSize[1]}");
                            $validIcons[] = ['url' => $icon['url'], 'width' => $imageSize[0], 'height' => $imageSize[1], 'format' => 'ico'];
                            continue;
                         }
                    }

                    // For other types, or if getimagesize on URL fails for ICO, try fetching a small part or rely on Content-Type
                    // This part can be complex. For now, we'll be optimistic if it's an image type.
                    // A more robust solution would involve partial downloads or a library.
                    // For simplicity, if it's an image type and 'sizes' wasn't present, we might assign a default low score or skip.
                    // Or, if we must download, ensure it's small.
                    $contentType = $headResponse->header('Content-Type');
                    if (Str::startsWith($contentType, 'image/') && $contentType !== 'image/svg+xml') {
                        // Could attempt to download and use getimagesize on temp file, but adds complexity & risk.
                        // For now, if no 'sizes' attribute, we'll give it a lower priority or skip if other sized icons exist.
                        // Let's assume if 'sizes' is missing, it's a less specified icon.
                        Log::info("FaviconExtractor: Icon {$icon['url']} is an image but 'sizes' attribute was not definitive. Type: {$contentType}");
                        // We could add it with a default small size to rank it lower if no other info.
                        // $validIcons[] = ['url' => $icon['url'], 'width' => 16, 'height' => 16, 'format' => $fileExtension];
                    }
                }

            } catch (Exception $e) {
                Log::warning("FaviconExtractor: Error processing icon {$icon['url']}: " . $e->getMessage());
            }
        }

        if (empty($validIcons)) {
            Log::info("FaviconExtractor: No valid/accessible favicons found after processing candidates.");
            return null;
        }

        // Sort by area (width*height) descending, then by preferred format
        usort($validIcons, function ($a, $b) {
            $areaA = ($a['width'] ?? 0) * ($a['height'] ?? 0);
            $areaB = ($b['width'] ?? 0) * ($b['height'] ?? 0);

            if ($areaA !== $areaB) {
                return $areaB <=> $areaA; // Sort by area descending
            }

            // If areas are equal, sort by preferred format
            $formatAIndex = array_search($a['format'], $this->preferredFormats);
            $formatBIndex = array_search($b['format'], $this->preferredFormats);

            // Handle cases where format might not be in preferredFormats (treat as lowest priority)
            $formatAIndex = ($formatAIndex === false) ? count($this->preferredFormats) : $formatAIndex;
            $formatBIndex = ($formatBIndex === false) ? count($this->preferredFormats) : $formatBIndex;
            
            return $formatAIndex <=> $formatBIndex; // Sort by format index ascending (lower index is better)
        });
        
        Log::info("FaviconExtractor: Best icon selected: " . $validIcons[0]['url'] . " (Dimensions: {$validIcons[0]['width']}x{$validIcons[0]['height']}, Format: {$validIcons[0]['format']})");
        return $validIcons[0]['url'];
    }

    protected function parseSizesAttribute(string $sizesAttr): ?array
    {
        if (empty($sizesAttr) || strtolower($sizesAttr) === 'any') {
            return null;
        }
        // Get the first size declaration, e.g., "192x192" from "192x192 128x128"
        $sizeParts = explode(' ', trim($sizesAttr));
        if (empty($sizeParts[0])) return null;

        $dimensions = explode('x', strtolower($sizeParts[0]));
        if (count($dimensions) === 2 && is_numeric($dimensions[0]) && is_numeric($dimensions[1])) {
            return ['width' => (int)$dimensions[0], 'height' => (int)$dimensions[1]];
        }
        return null;
    }
}