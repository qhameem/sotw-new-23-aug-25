<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler; // For parsing HTML
use Illuminate\Support\Str; // For string manipulations
use Exception;
use Illuminate\Support\Facades\Cache;

class FaviconExtractorService
{
    protected int $timeoutSeconds = 5; // Default timeout for HTTP requests
    protected array $preferredFormats = ['svg', 'png', 'webp', 'ico', 'jpg', 'jpeg', 'gif'];

    public function __construct(int $timeoutSeconds = 5)
    {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    /**
     * Extracts the best quality logo or icon URL from a given website URL.
     *
     * @param string $url The URL of the website.
     * @return array An array of the best logo URLs, empty if none found.
     */
    public function extract(string $url): array
    {
        $cacheKey = 'favicon_extractor_' . md5($url);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Step 1: Normalize the URL
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }
        
        $baseUrl = $this->getBaseUrl($url);
        if (!$baseUrl) {
            Log::error("FaviconExtractor: Could not determine base URL for {$url}");
            return [];
        }

        // Step 2: Fetch homepage HTML
        try {
            $response = Http::timeout($this->timeoutSeconds)->get($url);
            if (!$response->successful()) {
                Log::warning("FaviconExtractor: Failed to fetch main page {$url}. Status: " . $response->status());
                $fallback = $this->getFallbackFaviconIco($baseUrl);
                $result = $fallback ? [$fallback] : [];
                Cache::put($cacheKey, $result, now()->addHours(24));
                return $result;
            }
            $htmlContent = $response->body();
            $crawler = new Crawler($htmlContent, $url);
        } catch (Exception $e) {
            Log::error("FaviconExtractor: Error fetching main page {$url}: " . $e->getMessage());
            $fallback = $this->getFallbackFaviconIco($baseUrl);
            $result = $fallback ? [$fallback] : [];
            Cache::put($cacheKey, $result, now()->addHours(24));
            return $result;
        }

        // Step 3: Initialize empty candidate list
        $candidates = [];

        // Step 4: Parse <head> for icons
        $crawler->filter('link[rel*="icon"], link[rel*="apple-touch-icon"], link[rel*="mask-icon"]')->each(function (Crawler $node) use (&$candidates, $baseUrl) {
            if ($href = $node->attr('href')) {
                $candidates[] = ['url' => $this->makeAbsoluteUrl($href, $baseUrl), 'source' => 'link_tag'];
            }
        });

        // Step 5: Check for OpenGraph / Twitter images
        $crawler->filter('meta[property="og:image"], meta[name="twitter:image"]')->each(function (Crawler $node) use (&$candidates, $baseUrl) {
            if ($content = $node->attr('content')) {
                $candidates[] = ['url' => $this->makeAbsoluteUrl($content, $baseUrl), 'source' => 'meta_tag'];
            }
        });

        // Step 6: Look for <img> logos in common header/nav areas
        $crawler->filter('header a img, nav a img, [role="banner"] a img')->each(function (Crawler $node) use (&$candidates, $baseUrl) {
            $linkNode = $node->ancestors()->filter('a')->first();
            $href = $linkNode->count() ? $linkNode->attr('href') : '';
            $absoluteLink = $this->makeAbsoluteUrl($href, $baseUrl);

            // Prioritize images that link back to the homepage
            if ($href === '/' || $absoluteLink === $baseUrl . '/') {
                $src = $node->attr('src');
                if ($src) {
                    $candidates[] = ['url' => $this->makeAbsoluteUrl($src, $baseUrl), 'source' => 'img_tag_home_link'];
                }
            }
        });

        // Broader search if the specific one fails
        if (empty(array_filter($candidates, fn($c) => $c['source'] === 'img_tag_home_link'))) {
            $crawler->filter('header img, nav img')->each(function (Crawler $node) use (&$candidates, $baseUrl) {
                $src = $node->attr('src');
                $alt = $node->attr('alt');
                $filename = basename(parse_url($src, PHP_URL_PATH));
                if ($src && (Str::contains(strtolower($alt), ['logo', 'brand']) || Str::contains(strtolower($filename), ['logo', 'brand']))) {
                     $candidates[] = ['url' => $this->makeAbsoluteUrl($src, $baseUrl), 'source' => 'img_tag'];
                }
            });
        }

        // Step 7: Add fallback favicon
        $candidates[] = ['url' => $this->makeAbsoluteUrl('/favicon.ico', $baseUrl), 'source' => 'fallback_ico'];

        // Step 8 & 9: Score each candidate and select the best ones
        $result = $this->findBestLogos($candidates);
        Cache::put($cacheKey, $result, now()->addHours(24));
        return $result;
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
            $response = Http::timeout($this->timeoutSeconds / 2)->head($faviconIcoUrl);
            if ($response->successful()) {
                Log::info("FaviconExtractor: Found fallback /favicon.ico at {$faviconIcoUrl}");
                return $faviconIcoUrl;
            }
        } catch (Exception $e) {
            Log::warning("FaviconExtractor: Error checking fallback /favicon.ico at {$faviconIcoUrl}: " . $e->getMessage());
        }
        return null;
    }

    protected function findBestLogos(array $candidates): array
    {
        $scoredLogos = [];
        $processedUrls = [];

        foreach ($candidates as $candidate) {
            if (empty($candidate['url']) || in_array($candidate['url'], $processedUrls)) {
                continue;
            }
            $processedUrls[] = $candidate['url'];

            $score = $this->scoreLogo($candidate); // Pass the whole candidate
            if ($score > 0) {
                $scoredLogos[] = ['url' => $candidate['url'], 'score' => $score];
            }
        }

        if (empty($scoredLogos)) {
            Log::info("FaviconExtractor: No valid/accessible logos found after scoring.");
            return [];
        }

        // Sort by score descending
        // Separate SVGs from other formats
        $svgLogos = array_filter($scoredLogos, fn($logo) => Str::endsWith(strtolower($logo['url']), '.svg'));
        $otherLogos = array_filter($scoredLogos, fn($logo) => !Str::endsWith(strtolower($logo['url']), '.svg'));

        // Sort each group by score descending
        usort($svgLogos, fn($a, $b) => $b['score'] <=> $a['score']);
        usort($otherLogos, fn($a, $b) => $b['score'] <=> $a['score']);

        // Combine the lists, with SVGs first
        $sortedLogos = array_merge($svgLogos, $otherLogos);

        // Return the URLs of the top 3 logos
        $topLogos = array_slice($sortedLogos, 0, 3);
        Log::info("FaviconExtractor: Top logos selected: ", $topLogos);
        return array_column($topLogos, 'url');
    }

    protected function scoreLogo(array $candidate): int
    {
        $imageUrl = $candidate['url'];
        $source = $candidate['source'];
        $score = 0;

        $urlPath = parse_url($imageUrl, PHP_URL_PATH);
        if (!$urlPath) return 0;

        $filename = strtolower(basename($urlPath));
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // 1. Score based on source
        if ($source === 'img_tag_home_link') $score += 200;
        if ($source === 'link_tag') $score += 150;
        if ($source === 'meta_tag') $score += 50; // OG images are often not logos
        if ($source === 'img_tag') $score += 25;

        // 2. Score based on format
        if ($extension === 'svg') $score += 500;
        if (in_array($extension, ['png', 'webp'])) $score += 50;

        // 3. Score based on filename keywords
        if (Str::contains($filename, 'logo')) $score += 100;
        if (Str::contains($filename, 'brand')) $score += 50;
        if (Str::contains($filename, 'icon')) $score += 10;
        
        // Penalize non-logo keywords
        $badKeywords = ['hero', 'banner', 'screenshot', 'illustration', 'background', 'og', 'twitter'];
        foreach ($badKeywords as $keyword) {
            if (Str::contains($filename, $keyword)) $score -= 100;
        }
        if (Str::contains($filename, 'favicon')) $score -= 50;

        // 4. Score based on dimensions (if not SVG)
        if ($extension !== 'svg') {
            try {
                $imageSize = @getimagesize($imageUrl);
                if ($imageSize && $imageSize[0] > 0 && $imageSize[1] > 0) {
                    $width = $imageSize[0];
                    $height = $imageSize[1];

                    // Penalize very large images (likely not logos)
                    if ($width > 1000 || $height > 1000) {
                        $score -= 200;
                    }

                    // Add points for being square-ish
                    $aspectRatio = $width / $height;
                    if ($aspectRatio > 0.8 && $aspectRatio < 1.2) $score += 50;

                    // Add points for ideal logo dimensions
                    if (($width >= 64 && $width <= 512) && ($height >= 64 && $height <= 512)) {
                        $score += 50;
                    }
                }
            } catch (Exception $e) {
                // Could not get dimensions, do nothing.
            }
        }

        // 5. Final check for accessibility
        try {
             $headResponse = Http::timeout($this->timeoutSeconds / 2)->head($imageUrl);
             if (!$headResponse->successful()) return 0;
        } catch (Exception $ex) {
            return 0;
        }
        
        return max(0, $score); // Ensure score is not negative
    }
}