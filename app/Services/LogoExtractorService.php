<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use DOMDocument;

class LogoExtractorService
{
    // Minimum pixel area to be considered "acceptable quality" when better options exist
    private const MIN_QUALITY_DIMENSION = 48;
    // Aspect ratio limit — images wider than this are likely banners, not logos
    private const MAX_ASPECT_RATIO = 2.0;
    // Timeout for probing image dimensions
    private const IMAGE_PROBE_TIMEOUT = 4;

    public function extract(string $url, string $html): array
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);

        // ── Candidate collection ──────────────────────────────────────────────
        // Each candidate is ['url' => string, 'tier' => int, 'source' => string]
        // Lower tier = higher priority (Tier 1 > Tier 2 > Tier 3 > Tier 4)
        $candidates = [];

        $links = $doc->getElementsByTagName('link');
        $metas = $doc->getElementsByTagName('meta');
        $xpath = new \DOMXPath($doc);
        $parsedBase = parse_url($url);
        $domain = $parsedBase['host'] ?? '';

        // ── TIER 1: apple-touch-icon & PWA manifest icons ─────────────────────
        // These are specifically designed as square brand icons.
        foreach ($links as $link) {
            if (!($link instanceof \DOMElement))
                continue;
            $rel = strtolower($link->getAttribute('rel'));
            if (in_array($rel, ['apple-touch-icon', 'apple-touch-icon-precomposed'])) {
                $candidates[] = [
                    'url' => $this->resolveUrl($url, $link->getAttribute('href')),
                    'tier' => 1,
                    'source' => 'apple-touch-icon',
                    'sizes' => $link->getAttribute('sizes'),
                ];
            }
        }

        // PWA Web App Manifest icons (often 192×192 or 512×512)
        foreach ($links as $link) {
            if (!($link instanceof \DOMElement))
                continue;
            if (strtolower($link->getAttribute('rel')) === 'manifest') {
                $manifestUrl = $this->resolveUrl($url, $link->getAttribute('href'));
                try {
                    $manifestContent = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                    ])->timeout(5)->get($manifestUrl)->json();
                    if (!empty($manifestContent['icons'])) {
                        // Sort by size descending to prefer larger icons
                        $icons = $manifestContent['icons'];
                        usort($icons, function ($a, $b) {
                            $sizeA = $this->parseSizeAttribute($a['sizes'] ?? '');
                            $sizeB = $this->parseSizeAttribute($b['sizes'] ?? '');
                            return $sizeB - $sizeA;
                        });
                        foreach ($icons as $icon) {
                            $candidates[] = [
                                'url' => $this->resolveUrl($url, $icon['src']),
                                'tier' => 1,
                                'source' => 'pwa-manifest',
                                'sizes' => $icon['sizes'] ?? '',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        // ── TIER 2: Structural logo images (header/nav/logo-class/home-link) ──
        // These are images actually used ON the page as the visible logo.
        $structuralQueries = [
            "//a[@href='/' or @href='' or @href='{$url}' or contains(@href, '{$domain}')]//img",
            "//header//img",
            "//nav//img",
            "//*[contains(@id, 'logo') or contains(@class, 'logo')]//img",
            "//*[contains(@id, 'brand') or contains(@class, 'brand')]//img",
            "//header//svg",
            "//nav//svg",
            "//*[contains(@id, 'logo') or contains(@class, 'logo')]//svg",
        ];
        foreach ($structuralQueries as $query) {
            $nodes = $xpath->query($query);
            foreach ($nodes as $img) {
                if (!($img instanceof \DOMElement))
                    continue;

                if ($img->tagName === 'svg') {
                    // Convert inline SVG to Data URL for frontend preview
                    $svgContent = $doc->saveHTML($img);
                    $candidates[] = [
                        'url' => 'data:image/svg+xml;base64,' . base64_encode($svgContent),
                        'tier' => 2,
                        'source' => 'structural-svg',
                        'sizes' => '',
                    ];
                    continue;
                }

                $src = $img->getAttribute('src')
                    ?: $img->getAttribute('data-src')
                    ?: $img->getAttribute('data-lazy-src');
                if ($src) {
                    $candidates[] = [
                        'url' => $this->resolveUrl($url, $src),
                        'tier' => 2,
                        'source' => 'structural',
                        'sizes' => '',
                    ];
                }
            }
        }

        // Images with "logo" keyword in src/alt/class/id
        $images = $doc->getElementsByTagName('img');
        foreach ($images as $img) {
            if (!($img instanceof \DOMElement))
                continue;
            $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');
            $alt = $img->getAttribute('alt');
            $class = $img->getAttribute('class');
            $id = $img->getAttribute('id');
            if (
                $src && (
                    preg_match('/logo/i', $src) ||
                    preg_match('/logo/i', $alt) ||
                    preg_match('/logo/i', $class) ||
                    preg_match('/logo/i', $id)
                )
            ) {
                $candidates[] = [
                    'url' => $this->resolveUrl($url, $src),
                    'tier' => 2,
                    'source' => 'logo-keyword',
                    'sizes' => '',
                ];
            }
        }

        // ── TIER 3: Microlink API & JSON-LD & mask-icon & JS Payloads ──────────
        // Good but not guaranteed to be the primary brand icon.
        try {
            $mlResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->timeout(10)->get('https://api.microlink.io?url=' . urlencode($url));
            if ($mlResponse->successful()) {
                $mlData = $mlResponse->json();
                if (!empty($mlData['data']['logo']['url'])) {
                    $candidates[] = [
                        'url' => $mlData['data']['logo']['url'],
                        'tier' => 3,
                        'source' => 'microlink',
                        'sizes' => '',
                    ];
                }
            }
        } catch (\Exception $e) {
        }

        // JSON-LD Organization/Brand logo
        $scripts = $doc->getElementsByTagName('script');
        foreach ($scripts as $script) {
            if (!($script instanceof \DOMElement))
                continue;
            if ($script->getAttribute('type') !== 'application/ld+json')
                continue;
            try {
                $json = json_decode($script->nodeValue, true);
                if ($json) {
                    $this->extractJsonLDCandidates($json, $candidates, $url);
                }
            } catch (\Exception $e) {
            }
        }

        // Parse JS payloads (Next.js, etc.) for hidden icons
        $this->extractJsPayloadCandidates($scripts, $candidates, $url);

        // Mask icon (usually SVG)
        foreach ($links as $link) {
            if (!($link instanceof \DOMElement))
                continue;
            if (strtolower($link->getAttribute('rel')) === 'mask-icon') {
                $candidates[] = [
                    'url' => $this->resolveUrl($url, $link->getAttribute('href')),
                    'tier' => 3,
                    'source' => 'mask-icon',
                    'sizes' => '',
                ];
            }
        }

        // ── TIER 4: Fallback — standard favicons and og:image ──────────────────
        foreach ($links as $link) {
            if (!($link instanceof \DOMElement))
                continue;
            $rel = strtolower($link->getAttribute('rel'));
            if (in_array($rel, ['icon', 'shortcut icon'])) {
                $candidates[] = [
                    'url' => $this->resolveUrl($url, $link->getAttribute('href')),
                    'tier' => 4,
                    'source' => 'favicon',
                    'sizes' => $link->getAttribute('sizes'),
                ];
            }
        }

        foreach ($metas as $meta) {
            if (!($meta instanceof \DOMElement))
                continue;
            $property = strtolower($meta->getAttribute('property') ?: $meta->getAttribute('name'));
            if (in_array($property, ['og:image', 'twitter:image'])) {
                $candidates[] = [
                    'url' => $this->resolveUrl($url, $meta->getAttribute('content')),
                    'tier' => 4,
                    'source' => 'og-image',
                    'sizes' => '',
                ];
            }
        }

        // ── Deduplication ─────────────────────────────────────────────────────
        $seen = [];
        $unique = [];
        foreach ($candidates as $c) {
            if (!empty($c['url']) && !isset($seen[$c['url']])) {
                $seen[$c['url']] = true;
                $unique[] = $c;
            }
        }

        // ── Quality gating & ranking ──────────────────────────────────────────
        return $this->qualityGateAndRank($unique, $url, $doc);
    }

    // ─── JSON-LD helper ───────────────────────────────────────────────────────

    private function extractJsonLDCandidates(array $json, array &$candidates, string $url): void
    {
        if (isset($json['@graph']) && is_array($json['@graph'])) {
            foreach ($json['@graph'] as $item) {
                $this->extractJsonLDCandidates($item, $candidates, $url);
            }
            return;
        }

        $types = (array) ($json['@type'] ?? []);
        $isBrand = !empty(array_intersect($types, ['Organization', 'Brand', 'WebSite', 'Product']));

        if ($isBrand && isset($json['logo'])) {
            $logo = $json['logo'];
            $logoUrl = is_string($logo) ? $logo : ($logo['url'] ?? null);
            if ($logoUrl) {
                $candidates[] = [
                    'url' => $this->resolveUrl($url, $logoUrl),
                    'tier' => 3,
                    'source' => 'json-ld',
                    'sizes' => is_array($logo) ? (($logo['width'] ?? '') . 'x' . ($logo['height'] ?? '')) : '',
                ];
            }
        }
    }

    /**
     * Scan script tags for common JS-driven metadata patterns.
     * Useful for Next.js, Nuxt, and React apps that hydrate metadata.
     */
    private function extractJsPayloadCandidates(\DOMNodeList $scripts, array &$candidates, string $url): void
    {
        foreach ($scripts as $script) {
            $content = $script->nodeValue;
            if (empty($content) || strlen($content) < 50)
                continue;

            // Look for URL patterns associated with logos/icons/images
            // Specifically targeting Next.js __NEXT_DATA__ or hydration pushes
            $patterns = [
                '/"logo"\s*:\s*"([^"]+\.(?:png|jpg|jpeg|svg|webp|ico)[^"]*)"/i',
                '/"icon"\s*:\s*"([^"]+\.(?:png|jpg|jpeg|svg|webp|ico)[^"]*)"/i',
                '/"og:image"\s*:\s*"([^"]+\.(?:png|jpg|jpeg|svg|webp|ico)[^"]*)"/i',
                '/"brand"\s*:\s*"([^"]+\.(?:png|jpg|jpeg|svg|webp|ico)[^"]*)"/i',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $imgUrl) {
                        // Clean character escapes if any (e.g. \/)
                        $imgUrl = str_replace('\\/', '/', $imgUrl);
                        $resolved = $this->resolveUrl($url, $imgUrl);
                        if ($resolved) {
                            $candidates[] = [
                                'url' => $resolved,
                                'tier' => 3,
                                'source' => 'js-payload',
                                'sizes' => '',
                            ];
                        }
                    }
                }
            }
        }
    }

    // ─── Quality gate & ranker ────────────────────────────────────────────────

    private function qualityGateAndRank(array $candidates, string $url, DOMDocument $doc): array
    {
        // Step 1: Validate format (must be an image)
        $valid = array_filter($candidates, function ($c) {
            $path = parse_url($c['url'], PHP_URL_PATH);
            if (!$path)
                return false;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico', 'bmp', 'avif'])
                || Str::contains($c['url'], ['data:image/', 'microlink']);
        });

        // Step 2: Probe actual dimensions for raster images (skip SVGs — always vector)
        $probed = [];
        foreach ($valid as $c) {
            $c['width'] = 0;
            $c['height'] = 0;
            $c['area'] = 0;

            $ext = strtolower(pathinfo(parse_url($c['url'], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

            if ($ext === 'svg') {
                // SVGs are always vector — treat as "infinite" resolution
                $c['width'] = 9999;
                $c['height'] = 9999;
                $c['area'] = 9999 * 9999;
            } elseif ($ext === 'ico') {
                // ICOs are almost always 16×16 or 32×32 — too small for logos
                $c['width'] = 32;
                $c['height'] = 32;
                $c['area'] = 32 * 32;
            } else {
                // Try to parse size hints from the 'sizes' attribute (e.g. "192x192")
                $hintArea = $this->parseSizeAttribute($c['sizes'] ?? '');
                if ($hintArea > 0) {
                    $side = (int) sqrt($hintArea);
                    $c['width'] = $side;
                    $c['height'] = $side;
                    $c['area'] = $hintArea;
                } else {
                    // Try to extract dimensions from URL pattern (e.g. _512x512 or -192x192)
                    if (preg_match('/[_\-x](\d{2,4})[x×](\d{2,4})/i', $c['url'], $m)) {
                        $c['width'] = (int) $m[1];
                        $c['height'] = (int) $m[2];
                        $c['area'] = $c['width'] * $c['height'];
                    } else {
                        // Probe via getimagesize — fast HEAD + minimal download
                        try {
                            $response = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                                'Range' => 'bytes=0-32767'
                            ])->timeout(self::IMAGE_PROBE_TIMEOUT)
                                ->get($c['url']);
                            if ($response->successful()) {
                                $imageData = @getimagesizefromstring($response->body());
                                if ($imageData) {
                                    $c['width'] = $imageData[0];
                                    $c['height'] = $imageData[1];
                                    $c['area'] = $imageData[0] * $imageData[1];
                                }
                            }
                        } catch (\Exception $e) {
                            // Can't probe — leave at 0, will not be discarded unless better exist
                        }
                    }
                }
            }

            $probed[] = $c;
        }

        // Step 3: Find the best known quality across all candidates
        $bestKnownDim = max(array_column($probed, 'width') ?: [0]);

        // Step 4: Hard quality gate — discard CLEARLY inferior candidates only if
        //         we have solid better options (to avoid throwing everything away)
        $qualityThreshold = self::MIN_QUALITY_DIMENSION;
        $filtered = array_filter($probed, function ($c) use ($bestKnownDim, $qualityThreshold) {
            // Always keep if we couldn't probe (area = 0) — don't penalise the unknown
            if ($c['area'] === 0 && $c['width'] === 0)
                return true;

            // Hard discard: too small AND we have better options
            if ($bestKnownDim > $qualityThreshold && $c['width'] > 0 && $c['width'] < $qualityThreshold) {
                return false;
            }

            // Hard discard: wide banner / social card
            if ($c['width'] > 0 && $c['height'] > 0) {
                $ratio = $c['width'] / $c['height'];
                if ($ratio > self::MAX_ASPECT_RATIO)
                    return false;
            }

            // Hard discard: og-image only when better tiers have candidates
            // (og-image is kept only as a last resort)
            return true;
        });

        // If hard discard removed everything, fall back to original (safety net)
        if (empty($filtered)) {
            $filtered = $probed;
        }

        // Step 5: Score & rank remaining candidates
        $xpath = new \DOMXPath($doc);
        $parsedUrl = parse_url($url);
        $domain = $parsedUrl['host'] ?? '';

        $scored = [];
        $hasTier = [];
        foreach ($filtered as $c) {
            $hasTier[$c['tier']] = true;
        }
        $bestTierAvailable = min(array_keys($hasTier));

        foreach ($filtered as $c) {
            $score = 0;
            $logo = $c['url'];
            $source = $c['source'];
            $tier = $c['tier'];
            $w = $c['width'];
            $h = $c['height'];
            $filename = basename($logo);

            // A. Tier bonus (lower tier = higher quality source)
            $score += (5 - $tier) * 20; // Tier 1 → +80, Tier 2 → +60, Tier 3 → +40, Tier 4 → +20

            // Penalise og-image unless it's the only thing we have
            if ($source === 'og-image' && $bestTierAvailable < 4) {
                $score -= 40;
            }

            // B. Resolution bonus (larger = better, up to a cap — don't over-favour huge social cards)
            if ($w > 0 && $h > 0) {
                $minSide = min($w, $h);
                if ($minSide >= 512)
                    $score += 20;
                elseif ($minSide >= 192)
                    $score += 15;
                elseif ($minSide >= 128)
                    $score += 10;
                elseif ($minSide >= 64)
                    $score += 5;
            }

            // C. Format bonus
            $ext = strtolower(pathinfo(parse_url($logo, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if ($ext === 'svg')
                $score += 35; // Vector — absolute best quality
            elseif ($ext === 'png')
                $score += 8;
            elseif ($ext === 'webp')
                $score += 6;
            elseif ($ext === 'ico')
                $score -= 10;

            // D. Square aspect ratio bonus
            if ($w > 0 && $h > 0) {
                $ratio = $w / $h;
                if ($ratio >= 0.85 && $ratio <= 1.15)
                    $score += 10; // Nearly square
            }

            // E. Source-specific bonuses
            if ($source === 'apple-touch-icon')
                $score += 15;
            if ($source === 'pwa-manifest')
                $score += 12;
            if ($source === 'logo-keyword')
                $score += 10;
            if ($source === 'mask-icon')
                $score += 8;
            if ($source === 'microlink')
                $score += 8;

            // F. Structural placement in page
            if (strlen($filename) > 3) {
                $headerMatch = $xpath->query("//header//img[contains(@src, '{$filename}')]");
                if ($headerMatch->length > 0)
                    $score += 25;

                $navMatch = $xpath->query("//nav//img[contains(@src, '{$filename}')]");
                if ($navMatch->length > 0)
                    $score += 20;

                $homeAnchorMatch = $xpath->query("//a[@href='/' or @href='' or contains(@href, '{$domain}')]//img[contains(@src, '{$filename}')]");
                if ($homeAnchorMatch->length > 0)
                    $score += 30;
            }

            // G. Keyword signals in URL
            if (stripos($logo, 'logo') !== false)
                $score += 10;
            if (stripos($logo, 'brand') !== false)
                $score += 8;
            if (stripos($logo, 'icon') !== false && $source !== 'apple-touch-icon')
                $score -= 5;

            // H. Penalise clearly low-quality patterns
            $blockKeywords = [
                'social',
                'facebook',
                'twitter',
                'instagram',
                'linkedin',
                'banner',
                'hero',
                'background',
                'bg',
                'placeholder',
                'avatar',
                'user',
                'button'
            ];
            foreach ($blockKeywords as $kw) {
                if (stripos($logo, $kw) !== false)
                    $score -= 20;
            }
            if (stripos($logo, 'favicon') !== false && $source !== 'apple-touch-icon')
                $score -= 40;

            $scored[] = ['url' => $logo, 'score' => $score, 'tier' => $tier, 'w' => $w, 'h' => $h];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        $topUrls = array_slice(array_column($scored, 'url'), 0, 12);

        // Convert top 3 to Data URLs to bypass CORS/Hotlinking restrictions in the browser
        return array_map(function ($url, $index) {
            if ($index < 3 && !Str::startsWith($url, 'data:')) {
                $dataUrl = $this->fetchAsDataUrl($url);
                return $dataUrl ?: $url;
            }
            return $url;
        }, $topUrls, array_keys($topUrls));
    }

    /**
     * Fetch a remote image and convert it to a Data URL.
     */
    private function fetchAsDataUrl(string $url): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->timeout(5)->get($url);

            if ($response->successful()) {
                $content = $response->body();
                $type = $response->header('Content-Type');
                if ($content && $type && Str::startsWith($type, 'image/')) {
                    return 'data:' . $type . ';base64,' . base64_encode($content);
                }
            }
        } catch (\Exception $e) {
            // Fall back to original URL
        }
        return null;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Parse a sizes attribute like "192x192" or "512x512 192x192" and return
     * the area of the largest listed size.
     */
    private function parseSizeAttribute(string $sizes): int
    {
        if (!$sizes || strtolower($sizes) === 'any')
            return 0;
        $max = 0;
        foreach (explode(' ', $sizes) as $part) {
            if (preg_match('/(\d+)[x×](\d+)/i', $part, $m)) {
                $area = (int) $m[1] * (int) $m[2];
                if ($area > $max)
                    $max = $area;
            }
        }
        return $max;
    }

    private function resolveUrl(string $baseUrl, string $relativeUrl): string
    {
        if (empty($relativeUrl))
            return '';
        if (Str::startsWith($relativeUrl, ['http://', 'https://']))
            return $relativeUrl;
        if (Str::startsWith($relativeUrl, '//'))
            return 'https:' . $relativeUrl;

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $path = $base['path'] ?? '';

        if (Str::startsWith($relativeUrl, '/')) {
            return "{$scheme}://{$host}" . $relativeUrl;
        }

        $path = rtrim(dirname($path), '/');
        // If path is root '/', dirname becomes '\' on some platforms or '.' on others.
        // Ensure we handle the root correctly to avoid double slashes.
        if ($path === '\\' || $path === '.' || $path === '/') {
            $path = '';
        }

        return "{$scheme}://{$host}/" . ltrim($path . '/' . ltrim($relativeUrl, '/'), '/');
    }
}