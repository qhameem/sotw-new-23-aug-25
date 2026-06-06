<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LaunchReadinessAuditService
{
    private const TITLE_RECOMMENDED_MIN = 30;
    private const TITLE_RECOMMENDED_MAX = 60;
    private const META_DESCRIPTION_RECOMMENDED_MIN = 120;
    private const META_DESCRIPTION_RECOMMENDED_MAX = 160;

    public function pendingReport(): array
    {
        $categories = [];

        foreach ($this->catalog() as $categoryKey => $category) {
            $checks = [];

            foreach ($category['checks'] as $checkKey => $check) {
                $checks[] = [
                    'key' => $checkKey,
                    'label' => $check['label'],
                    'description' => $check['description'],
                    'status' => 'pending',
                    'summary' => 'This check will run when analysis starts.',
                    'fix' => null,
                ];
            }

            $categories[] = [
                'key' => $categoryKey,
                'label' => $category['label'],
                'checks' => $checks,
            ];
        }

        return [
            'launch_score' => 0,
            'seo_score' => 0,
            'ai_score' => 0,
            'trust_score' => 0,
            'passed_checks' => 0,
            'warning_checks' => 0,
            'failed_checks' => 0,
            'status_label' => 'Awaiting scan',
            'summary' => [
                'submitted_url' => null,
                'normalized_url' => null,
                'final_url' => null,
                'scanned_at' => null,
                'page_title' => null,
                'fatal_error' => null,
            ],
            'categories' => $categories,
        ];
    }

    public function run(string $inputUrl): array
    {
        $startedAt = microtime(true);
        $normalizedUrl = $this->normalizeUrl($inputUrl);
        $this->assertSafeHost($normalizedUrl);

        $response = null;
        $fatalError = null;
        $robotsResponse = null;
        $sitemapResponse = null;

        try {
            $response = $this->fetch($normalizedUrl);
            $finalUrl = $response->effectiveUri()?->__toString() ?: $normalizedUrl;
            $siteRoot = $this->siteRoot($finalUrl);
            $robotsResponse = $this->fetchOptional($siteRoot.'/robots.txt');
            $sitemapResponse = $this->fetchOptional($siteRoot.'/sitemap.xml');
        } catch (\Throwable $exception) {
            $fatalError = $exception->getMessage();
            $finalUrl = $normalizedUrl;
        }

        $finalHost = parse_url($finalUrl, PHP_URL_HOST);
        $headers = $response?->headers() ?? [];
        $html = $response?->body() ?? '';
        $domState = $this->parseHtml($html);
        $content = $this->extractContentState(
            $domState['xpath'],
            is_string($finalHost) ? $finalHost : null,
            $finalUrl,
            isset($siteRoot) ? $siteRoot : $this->siteRoot($finalUrl)
        );
        $faviconState = $this->faviconState(
            $content['favicon'],
            $finalUrl,
            isset($siteRoot) ? $siteRoot : $this->siteRoot($finalUrl)
        );

        $resultsByKey = [
            'page_status' => $this->pageStatusResult($response, $fatalError),
            'https' => $this->httpsResult($finalUrl),
            'title_tag' => $this->titleResult($content['title']),
            'meta_description' => $this->metaDescriptionResult($content['meta_description']),
            'canonical_url' => $this->canonicalResult($content['canonical'], $finalUrl),
            'favicon' => $this->faviconResult($faviconState),
            'viewport_meta' => $this->viewportResult($content['viewport']),
            'html_lang' => $this->htmlLangResult($content['html_lang']),
            'h1_tag' => $this->h1Result($content['h1_count']),
            'heading_hierarchy' => $this->headingHierarchyResult($content['h1_count'], $content['h2_count']),
            'image_alt_text' => $this->imageAltResult($content['images_count'], $content['images_with_alt']),
            'https_redirects' => $this->httpsRedirectResult($normalizedUrl, $finalUrl),
            'indexability' => $this->indexabilityResult($content['robots_meta'], $headers['X-Robots-Tag'][0] ?? null),
            'security_headers' => $this->securityHeadersResult($headers),
            'compression' => $this->compressionResult($headers),
            'robots_txt' => $this->simpleFileResult($robotsResponse, 'robots.txt'),
            'sitemap_xml' => $this->simpleFileResult($sitemapResponse, 'sitemap.xml'),
            'form_labels' => $this->formLabelsResult($content['forms_count'], $content['labeled_inputs'], $content['inputs_count']),
            'landmarks' => $this->landmarksResult($content['landmarks']),
            'open_graph_basics' => $this->openGraphBasicsResult($content['og']),
            'open_graph_image' => $this->openGraphImageResult($content['og']['image'] ?? null),
            'twitter_card' => $this->twitterCardResult($content['twitter_card']),
            'structured_data' => $this->structuredDataResult($content['structured_data_count']),
            'internal_links' => $this->internalLinksResult($content['internal_links']),
            'external_links' => $this->externalLinksResult($content['external_links']),
            'privacy_contact' => $this->privacyContactResult($content['trust_links']),
            'primary_cta' => $this->primaryCtaResult($content['cta_texts']),
            'unique_viewpoint' => $this->uniqueViewpointResult($content['page_text']),
            'content_clarity' => $this->contentClarityResult($content['page_text'], $content['headings_count'], $content['paragraph_count']),
            'media_support' => $this->mediaSupportResult($content['images_count'], $content['video_count']),
            'crawlable_content' => $this->crawlableContentResult($content['page_text']),
            'javascript_dependency' => $this->javascriptDependencyResult($content['page_text'], $content['script_count']),
        ];

        $categories = [];
        $passed = 0;
        $warnings = 0;
        $failed = 0;

        foreach ($this->catalog() as $categoryKey => $category) {
            $checks = [];

            foreach ($category['checks'] as $checkKey => $check) {
                $result = $resultsByKey[$checkKey] ?? $this->unknownResult();
                $checks[] = [
                    'key' => $checkKey,
                    'label' => $check['label'],
                    'description' => $check['description'],
                    'status' => $result['status'],
                    'summary' => $result['summary'],
                    'fix' => in_array($result['status'], ['warning', 'fail'], true) ? ($check['fix'] ?? null) : null,
                    'meta' => $result['meta'] ?? [],
                ];

                if ($result['status'] === 'pass') {
                    $passed++;
                } elseif ($result['status'] === 'warning') {
                    $warnings++;
                } elseif ($result['status'] === 'fail') {
                    $failed++;
                }
            }

            $categories[] = [
                'key' => $categoryKey,
                'label' => $category['label'],
                'checks' => $checks,
            ];
        }

        [$seoScore, $aiScore, $trustScore, $launchScore] = $this->scoresFromResults($resultsByKey);
        $totalTestTimeSeconds = round(max(microtime(true) - $startedAt, 0), 2);

        return [
            'launch_score' => $launchScore,
            'seo_score' => $seoScore,
            'ai_score' => $aiScore,
            'trust_score' => $trustScore,
            'passed_checks' => $passed,
            'warning_checks' => $warnings,
            'failed_checks' => $failed,
            'status_label' => $this->statusLabel($launchScore),
            'summary' => [
                'submitted_url' => $inputUrl,
                'normalized_url' => $normalizedUrl,
                'final_url' => $finalUrl,
                'final_host' => $finalHost,
                'scanned_at' => now()->toIso8601String(),
                'page_title' => $content['title'],
                'fatal_error' => $fatalError,
                'total_test_time_seconds' => $totalTestTimeSeconds,
            ],
            'categories' => $categories,
        ];
    }

    public function normalizeUrl(string $inputUrl): string
    {
        $candidate = trim($inputUrl);

        if (! str_contains($candidate, '://')) {
            $candidate = 'https://'.$candidate;
        }

        if (! filter_var($candidate, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Please enter a valid public URL.');
        }

        $scheme = parse_url($candidate, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Only http and https URLs are supported.');
        }

        return $candidate;
    }

    private function assertSafeHost(string $url): void
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal') || str_ends_with($host, '.test')) {
            throw new RuntimeException('Please enter a publicly accessible URL.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('Private or reserved IP addresses are not allowed.');
            }

            return;
        }

        $resolvedIps = gethostbynamel($host) ?: [];

        foreach ($resolvedIps as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('Private or reserved destinations are not allowed.');
            }
        }
    }

    private function fetch(string $url): Response
    {
        return Http::accept('text/html,application/xhtml+xml')
            ->timeout(12)
            ->connectTimeout(6)
            ->withOptions([
                'allow_redirects' => ['max' => 5, 'track_redirects' => true],
                'http_errors' => false,
            ])
            ->get($url);
    }

    private function fetchOptional(string $url): ?Response
    {
        try {
            return Http::timeout(8)
                ->connectTimeout(4)
                ->withOptions([
                    'allow_redirects' => ['max' => 3, 'track_redirects' => true],
                    'http_errors' => false,
                ])
                ->get($url);
        } catch (ConnectionException) {
            return null;
        }
    }

    private function siteRoot(string $url): string
    {
        $parts = parse_url($url);

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }

    private function parseHtml(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html !== '' ? $html : '<html><head></head><body></body></html>');
        libxml_clear_errors();

        return [
            'dom' => $dom,
            'xpath' => new DOMXPath($dom),
        ];
    }

    private function extractContentState(DOMXPath $xpath, ?string $host = null, ?string $finalUrl = null, ?string $siteRoot = null): array
    {
        $title = trim((string) $xpath->evaluate('string(//title[1])'));
        $metaDescription = trim((string) $xpath->evaluate('string((//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]/@content)[1])'));
        $canonical = trim((string) $xpath->evaluate('string((//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]/@href)[1])'));
        $favicon = trim((string) $xpath->evaluate('string((//link[contains(translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"icon")]/@href)[1])'));
        $viewport = trim((string) $xpath->evaluate('string((//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="viewport"]/@content)[1])'));
        $htmlLang = trim((string) $xpath->evaluate('string(/html/@lang)'));
        $robotsMeta = trim((string) $xpath->evaluate('string((//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]/@content)[1])'));

        $og = [
            'title' => trim((string) $xpath->evaluate('string((//meta[@property="og:title"]/@content)[1])')),
            'description' => trim((string) $xpath->evaluate('string((//meta[@property="og:description"]/@content)[1])')),
            'type' => trim((string) $xpath->evaluate('string((//meta[@property="og:type"]/@content)[1])')),
            'image' => trim((string) $xpath->evaluate('string((//meta[@property="og:image"]/@content)[1])')),
        ];

        if ($og['image'] !== '' && $finalUrl && $siteRoot) {
            $og['image'] = $this->resolveAssetUrl($og['image'], $finalUrl, $siteRoot) ?? $og['image'];
        }

        $twitterCard = trim((string) $xpath->evaluate('string((//meta[@name="twitter:card"]/@content)[1])'));
        $structuredDataCount = (int) $xpath->evaluate('count(//script[contains(@type, "ld+json")])');
        $h1Count = (int) $xpath->evaluate('count(//h1)');
        $h2Count = (int) $xpath->evaluate('count(//h2)');
        $headingsCount = (int) $xpath->evaluate('count(//h1|//h2|//h3)');
        $paragraphCount = (int) $xpath->evaluate('count(//p[string-length(normalize-space(.)) > 0])');
        $scriptCount = (int) $xpath->evaluate('count(//script)');
        $videoCount = (int) $xpath->evaluate('count(//video|//iframe[contains(@src, "youtube") or contains(@src, "vimeo")])');
        $formsCount = (int) $xpath->evaluate('count(//form)');
        $landmarks = [
            'header' => (int) $xpath->evaluate('count(//header)'),
            'main' => (int) $xpath->evaluate('count(//main)'),
            'nav' => (int) $xpath->evaluate('count(//nav)'),
            'footer' => (int) $xpath->evaluate('count(//footer)'),
        ];

        $imagesCount = 0;
        $imagesWithAlt = 0;
        $inputsCount = 0;
        $labeledInputs = 0;
        $internalLinks = 0;
        $externalLinks = 0;
        $trustLinks = [];
        $ctaTexts = [];

        $nodes = $xpath->query('//img');

        foreach ($nodes ?: [] as $node) {
            $imagesCount++;
            $alt = trim((string) $node->attributes?->getNamedItem('alt')?->nodeValue);
            if ($alt !== '') {
                $imagesWithAlt++;
            }
        }

        $host = Str::lower((string) $host);

        foreach ($xpath->query('//input[not(@type="hidden")]|//textarea|//select') ?: [] as $node) {
            $inputsCount++;
            $id = trim((string) $node->attributes?->getNamedItem('id')?->nodeValue);
            $ariaLabel = trim((string) $node->attributes?->getNamedItem('aria-label')?->nodeValue);
            $ariaLabelledby = trim((string) $node->attributes?->getNamedItem('aria-labelledby')?->nodeValue);

            if ($ariaLabel !== '' || $ariaLabelledby !== '') {
                $labeledInputs++;

                continue;
            }

            if ($id !== '') {
                $escapedId = addslashes($id);
                $hasLabel = (int) $xpath->evaluate('count(//label[@for="'.$escapedId.'"])') > 0;
                if ($hasLabel) {
                    $labeledInputs++;
                }
            }
        }

        foreach ($xpath->query('//a[@href]') ?: [] as $node) {
            $href = trim((string) $node->attributes?->getNamedItem('href')?->nodeValue);
            $text = Str::lower(trim(preg_replace('/\s+/', ' ', $node->textContent ?? '')));

            if ($href === '') {
                continue;
            }

            if (Str::startsWith($href, ['mailto:', 'tel:'])) {
                $trustLinks[] = $href;

                continue;
            }

            if (Str::startsWith($href, '#')) {
                continue;
            }

            if (Str::startsWith($href, '/')) {
                $internalLinks++;
            } else {
                $hrefHost = Str::lower((string) parse_url($href, PHP_URL_HOST));
                if ($host !== '' && $hrefHost === $host) {
                    $internalLinks++;
                } elseif ($hrefHost !== '') {
                    $externalLinks++;
                }
            }

            if (preg_match('/privacy|contact|about|terms|security|support/', $href.' '.$text)) {
                $trustLinks[] = $href.' '.$text;
            }

            if (preg_match('/start|try|book|sign up|signup|get started|launch|submit|buy|download|contact sales|request demo/', $text)) {
                $ctaTexts[] = $text;
            }
        }

        $pageText = trim(preg_replace('/\s+/', ' ', $xpath->evaluate('string(//body)')));

        return [
            'title' => $title,
            'meta_description' => $metaDescription,
            'canonical' => $canonical,
            'favicon' => $favicon,
            'viewport' => $viewport,
            'html_lang' => $htmlLang,
            'robots_meta' => $robotsMeta,
            'og' => $og,
            'twitter_card' => $twitterCard,
            'structured_data_count' => $structuredDataCount,
            'h1_count' => $h1Count,
            'h2_count' => $h2Count,
            'headings_count' => $headingsCount,
            'paragraph_count' => $paragraphCount,
            'script_count' => $scriptCount,
            'video_count' => $videoCount,
            'forms_count' => $formsCount,
            'inputs_count' => $inputsCount,
            'labeled_inputs' => $labeledInputs,
            'landmarks' => $landmarks,
            'images_count' => $imagesCount,
            'images_with_alt' => $imagesWithAlt,
            'internal_links' => $internalLinks,
            'external_links' => $externalLinks,
            'trust_links' => $trustLinks,
            'cta_texts' => array_values(array_unique($ctaTexts)),
            'page_text' => $pageText,
        ];
    }

    private function pageStatusResult(?Response $response, ?string $fatalError): array
    {
        if ($fatalError) {
            return $this->fail("We couldn't load the page: {$fatalError}");
        }

        if (! $response) {
            return $this->fail("We couldn't load the page.");
        }

        if ($response->successful()) {
            return $this->pass('The submitted page loaded successfully.');
        }

        if ($response->redirect()) {
            return $this->warning('The URL redirects before landing on the final page.');
        }

        return $this->fail('The page did not return a successful response.');
    }

    private function httpsResult(string $finalUrl): array
    {
        return Str::startsWith($finalUrl, 'https://')
            ? $this->pass('The final page is served over HTTPS.')
            : $this->fail('The final page is not using HTTPS.');
    }

    private function titleResult(string $title): array
    {
        $length = Str::length($title);

        if ($length === 0) {
            return $this->fail('No HTML title tag was found.');
        }

        if ($length < self::TITLE_RECOMMENDED_MIN || $length > self::TITLE_RECOMMENDED_MAX) {
            return $this->warning("A title exists, but the length ({$length} chars) is outside the recommended " . self::TITLE_RECOMMENDED_MIN . '-' . self::TITLE_RECOMMENDED_MAX . ' character range.');
        }

        return $this->pass('The page has a clear title tag.');
    }

    private function metaDescriptionResult(string $description): array
    {
        $length = Str::length($description);

        if ($length === 0) {
            return $this->fail('No meta description was found.');
        }

        if ($length < self::META_DESCRIPTION_RECOMMENDED_MIN || $length > self::META_DESCRIPTION_RECOMMENDED_MAX) {
            return $this->warning("A meta description exists, but the length ({$length} chars) is outside the recommended " . self::META_DESCRIPTION_RECOMMENDED_MIN . '-' . self::META_DESCRIPTION_RECOMMENDED_MAX . ' character range.');
        }

        return $this->pass('The page has a usable meta description.');
    }

    private function canonicalResult(string $canonical, string $finalUrl): array
    {
        if ($canonical === '') {
            return $this->warning('No canonical URL was found.');
        }

        $canonicalHost = parse_url($canonical, PHP_URL_HOST);
        $finalHost = parse_url($finalUrl, PHP_URL_HOST);

        if ($canonicalHost && $finalHost && ! hash_equals(Str::lower($canonicalHost), Str::lower($finalHost))) {
            return $this->warning('A canonical URL exists, but it points to a different host.');
        }

        return $this->pass('A canonical URL is present.');
    }

    private function faviconResult(array $faviconState): array
    {
        $label = $faviconState['label'] ?? 'favicon.ico';
        $httpCode = $faviconState['http_code'];
        $previewUrl = $faviconState['preview_url'] ?? null;

        if ($faviconState['is_reachable']) {
            return $this->pass(
                "Favicon found and reachable: {$label} (HTTP {$httpCode}).",
                [
                    'preview_url' => $previewUrl,
                ]
            );
        }

        if ($faviconState['is_declared']) {
            $statusText = $httpCode ? "HTTP {$httpCode}" : 'unreachable';

            return $this->warning(
                "Favicon found but not reachable: {$label} ({$statusText}).",
                [
                    'preview_url' => $previewUrl,
                ]
            );
        }

        if ($httpCode) {
            return $this->warning("No favicon found or reachable: {$label} (HTTP {$httpCode}).");
        }

        return $this->warning('No favicon found or reachable.');
    }

    private function viewportResult(string $viewport): array
    {
        return $viewport !== ''
            ? $this->pass('A viewport meta tag is present.')
            : $this->warning('No viewport meta tag was detected.');
    }

    private function htmlLangResult(string $htmlLang): array
    {
        return $htmlLang !== ''
            ? $this->pass('The HTML lang attribute is set.')
            : $this->warning('The HTML lang attribute is missing.');
    }

    private function h1Result(int $count): array
    {
        if ($count === 0) {
            return $this->fail('No H1 heading was found.');
        }

        if ($count > 1) {
            return $this->warning("The page has {$count} H1 headings.");
        }

        return $this->pass('The page has a single H1 heading.');
    }

    private function headingHierarchyResult(int $h1Count, int $h2Count): array
    {
        if ($h1Count === 0) {
            return $this->fail('Heading hierarchy is weak because the page is missing an H1.');
        }

        if ($h2Count === 0) {
            return $this->warning('An H1 exists, but no H2 sections were found.');
        }

        return $this->pass('The page uses multiple heading levels.');
    }

    private function imageAltResult(int $imagesCount, int $imagesWithAlt): array
    {
        if ($imagesCount === 0) {
            return $this->warning('No images were found, so there is no visual support to evaluate.');
        }

        $ratio = $imagesWithAlt / max(1, $imagesCount);

        if ($ratio >= 0.8) {
            return $this->pass('Most images include alt text.');
        }

        if ($imagesWithAlt > 0) {
            return $this->warning('Some images are missing alt text.');
        }

        return $this->fail('Image alt text is missing across the page.');
    }

    private function httpsRedirectResult(string $normalizedUrl, string $finalUrl): array
    {
        if (Str::startsWith($normalizedUrl, 'https://') && Str::startsWith($finalUrl, 'https://')) {
            return $this->pass('The page resolves to an HTTPS destination.');
        }

        if (Str::startsWith($finalUrl, 'https://')) {
            return $this->warning('The URL reaches HTTPS, but the submitted URL did not start there.');
        }

        return $this->fail('The page does not end on an HTTPS destination.');
    }

    private function indexabilityResult(string $robotsMeta, ?string $xRobots): array
    {
        $value = Str::lower(trim($robotsMeta.' '.$xRobots));

        if (Str::contains($value, 'noindex')) {
            return $this->fail('The page signals noindex, which blocks search visibility.');
        }

        return $this->pass('No noindex directive was detected.');
    }

    private function securityHeadersResult(array $headers): array
    {
        $present = collect([
            'Strict-Transport-Security',
            'Content-Security-Policy',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'Referrer-Policy',
        ])->filter(fn (string $header) => ! empty($headers[$header]))->count();

        if ($present >= 3) {
            return $this->pass('Several useful security headers are present.');
        }

        if ($present >= 1) {
            return $this->warning('Some security headers are present, but coverage is limited.');
        }

        return $this->warning('No common security headers were detected.');
    }

    private function compressionResult(array $headers): array
    {
        $encoding = Str::lower((string) ($headers['Content-Encoding'][0] ?? ''));

        return preg_match('/br|gzip|deflate|zstd/', $encoding)
            ? $this->pass('Response compression is enabled.')
            : $this->warning('Compression was not detected on the page response.');
    }

    private function simpleFileResult(?Response $response, string $label): array
    {
        if ($response && $response->successful()) {
            return $this->pass("{$label} is available.");
        }

        return $this->warning("{$label} was not found at the standard path.");
    }

    private function formLabelsResult(int $formsCount, int $labeledInputs, int $inputsCount): array
    {
        if ($formsCount === 0 || $inputsCount === 0) {
            return $this->pass('No form fields were found that need label coverage.');
        }

        if ($labeledInputs === $inputsCount) {
            return $this->pass('Visible form controls appear to be labeled.');
        }

        if ($labeledInputs > 0) {
            return $this->warning('Some form controls appear to be missing labels.');
        }

        return $this->fail('Form controls appear to be missing labels.');
    }

    private function landmarksResult(array $landmarks): array
    {
        $count = collect($landmarks)->filter(fn (int $value) => $value > 0)->count();

        return $count >= 3
            ? $this->pass('The page uses several semantic landmarks.')
            : $this->warning('The page uses limited semantic landmarks.');
    }

    private function openGraphBasicsResult(array $og): array
    {
        $present = collect([$og['title'] ?? null, $og['description'] ?? null, $og['type'] ?? null])
            ->filter(fn (?string $value) => filled($value))
            ->count();

        if ($present > 0) {
            if ($present < 3) {
                return $this->warning('Some Open Graph fields are present, but the set is incomplete.');
            }

            $lengthWarnings = [];
            $ogTitleLength = Str::length((string) ($og['title'] ?? ''));
            $ogDescriptionLength = Str::length((string) ($og['description'] ?? ''));

            if ($ogTitleLength > 0 && ($ogTitleLength < self::TITLE_RECOMMENDED_MIN || $ogTitleLength > self::TITLE_RECOMMENDED_MAX)) {
                $lengthWarnings[] = "og:title ({$ogTitleLength} chars, recommended " . self::TITLE_RECOMMENDED_MIN . '-' . self::TITLE_RECOMMENDED_MAX . ')';
            }

            if ($ogDescriptionLength > 0 && ($ogDescriptionLength < self::META_DESCRIPTION_RECOMMENDED_MIN || $ogDescriptionLength > self::META_DESCRIPTION_RECOMMENDED_MAX)) {
                $lengthWarnings[] = "og:description ({$ogDescriptionLength} chars, recommended " . self::META_DESCRIPTION_RECOMMENDED_MIN . '-' . self::META_DESCRIPTION_RECOMMENDED_MAX . ')';
            }

            if ($lengthWarnings !== []) {
                return $this->warning('Core Open Graph fields are present, but recommended lengths could be improved for ' . implode(' and ', $lengthWarnings) . '.');
            }

            return $this->pass('Core Open Graph fields are present.');
        }

        return $this->warning('Open Graph basics were not detected.');
    }

    private function openGraphImageResult(?string $image): array
    {
        return filled($image)
            ? $this->pass('An Open Graph image is present.', ['preview_url' => $image])
            : $this->warning('No Open Graph image was detected.');
    }

    private function twitterCardResult(string $twitterCard): array
    {
        return $twitterCard !== ''
            ? $this->pass('A Twitter card tag is present.')
            : $this->warning('No Twitter card tag was detected.');
    }

    private function structuredDataResult(int $count): array
    {
        return $count > 0
            ? $this->pass('Structured data was detected.')
            : $this->warning('No JSON-LD structured data was detected.');
    }

    private function internalLinksResult(int $count): array
    {
        if ($count >= 3) {
            return $this->pass('The page includes multiple internal links.');
        }

        if ($count >= 1) {
            return $this->warning('The page includes only a small number of internal links.');
        }

        return $this->warning('No internal links were detected.');
    }

    private function externalLinksResult(int $count): array
    {
        return $count >= 1
            ? $this->pass('The page links out to at least one external destination.')
            : $this->warning('No external links were detected.');
    }

    private function privacyContactResult(array $trustLinks): array
    {
        return count($trustLinks) >= 1
            ? $this->pass('The page shows at least one trust or contact path.')
            : $this->warning('No obvious privacy, contact, or trust links were detected.');
    }

    private function primaryCtaResult(array $ctaTexts): array
    {
        return count($ctaTexts) >= 1
            ? $this->pass('A likely primary CTA was detected.')
            : $this->warning('No clear call to action was detected.');
    }

    private function uniqueViewpointResult(string $pageText): array
    {
        $signals = [
            '/why we built/i',
            '/our experience/i',
            '/case study/i',
            '/lessons learned/i',
            '/behind the scenes/i',
            '/customers?/i',
            '/results?/i',
            '/founded by/i',
            '/we spent/i',
        ];

        $matches = collect($signals)->filter(fn (string $pattern) => preg_match($pattern, $pageText) === 1)->count();

        if ($matches >= 2) {
            return $this->pass('The page shows signs of a specific point of view.');
        }

        if ($matches === 1) {
            return $this->warning('The page hints at a unique viewpoint, but it could be clearer.');
        }

        return $this->warning('The copy looks generic and could use more first-hand perspective.');
    }

    private function contentClarityResult(string $pageText, int $headingsCount, int $paragraphCount): array
    {
        $length = Str::length($pageText);

        if ($length >= 500 && $headingsCount >= 3 && $paragraphCount >= 2) {
            return $this->pass('The page content is structured and reasonably detailed.');
        }

        if ($length >= 250 && ($headingsCount >= 2 || $paragraphCount >= 1)) {
            return $this->warning('The content is understandable, but structure or depth could improve.');
        }

        return $this->fail('The page content looks thin or poorly structured.');
    }

    private function mediaSupportResult(int $imagesCount, int $videoCount): array
    {
        if (($imagesCount + $videoCount) >= 2) {
            return $this->pass('The page includes supporting media.');
        }

        if (($imagesCount + $videoCount) === 1) {
            return $this->warning('The page includes limited supporting media.');
        }

        return $this->warning('No meaningful supporting media was detected.');
    }

    private function crawlableContentResult(string $pageText): array
    {
        $length = Str::length($pageText);

        if ($length >= 500) {
            return $this->pass('A healthy amount of crawlable text content is present in the HTML.');
        }

        if ($length >= 250) {
            return $this->warning('Some crawlable content is present, but it is lighter than ideal.');
        }

        return $this->fail('Very little crawlable text content was found in the HTML response.');
    }

    private function javascriptDependencyResult(string $pageText, int $scriptCount): array
    {
        $length = Str::length($pageText);

        if ($scriptCount >= 8 && $length < 250) {
            return $this->fail('The page appears heavily dependent on JavaScript for visible content.');
        }

        if ($scriptCount >= 6 && $length < 500) {
            return $this->warning('The page may rely heavily on JavaScript for key content.');
        }

        return $this->pass('The page shows enough HTML content without obvious heavy JS dependence.');
    }

    private function faviconState(string $favicon, string $finalUrl, string $siteRoot): array
    {
        $rawCandidate = $favicon !== '' ? $favicon : '/favicon.ico';
        $resolvedUrl = $this->resolveAssetUrl($rawCandidate, $finalUrl, $siteRoot);
        $label = $this->assetLabel($rawCandidate, $resolvedUrl);

        if ($resolvedUrl === null) {
            return [
                'is_declared' => $favicon !== '',
                'is_reachable' => false,
                'http_code' => null,
                'label' => $label,
                'preview_url' => null,
            ];
        }

        if (Str::startsWith($resolvedUrl, 'data:')) {
            return [
                'is_declared' => true,
                'is_reachable' => true,
                'http_code' => 200,
                'label' => $label,
                'preview_url' => $resolvedUrl,
            ];
        }

        try {
            $this->assertSafeHost($resolvedUrl);
            $response = $this->fetchOptional($resolvedUrl);
        } catch (\Throwable) {
            $response = null;
        }

        return [
            'is_declared' => $favicon !== '',
            'is_reachable' => (bool) $response?->successful(),
            'http_code' => $response?->status(),
            'label' => $label,
            'preview_url' => $response?->successful() ? $resolvedUrl : null,
        ];
    }

    private function resolveAssetUrl(string $asset, string $finalUrl, string $siteRoot): ?string
    {
        if ($asset === '') {
            return null;
        }

        if (Str::startsWith($asset, 'data:')) {
            return $asset;
        }

        if (Str::startsWith($asset, ['http://', 'https://'])) {
            return $asset;
        }

        $parts = parse_url($finalUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        if (Str::startsWith($asset, '//')) {
            return "{$scheme}:{$asset}";
        }

        if (Str::startsWith($asset, '/')) {
            return "{$scheme}://{$host}{$port}{$asset}";
        }

        $path = $parts['path'] ?? '/';
        $directory = str_contains($path, '/') ? preg_replace('~/[^/]*$~', '/', $path) : '/';

        return "{$scheme}://{$host}{$port}{$directory}{$asset}";
    }

    private function assetLabel(string $rawCandidate, ?string $resolvedUrl): string
    {
        if (Str::startsWith($rawCandidate, 'data:')) {
            return 'inline data URL';
        }

        $path = parse_url($resolvedUrl ?: $rawCandidate, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $rawCandidate !== '' ? $rawCandidate : 'favicon.ico';
        }

        $basename = basename($path);

        return $basename !== '' ? $basename : ltrim($path, '/');
    }

    private function unknownResult(): array
    {
        return $this->warning('This check was not evaluated.');
    }

    private function pass(string $summary, array $meta = []): array
    {
        return ['status' => 'pass', 'summary' => $summary, 'meta' => $meta];
    }

    private function warning(string $summary, array $meta = []): array
    {
        return ['status' => 'warning', 'summary' => $summary, 'meta' => $meta];
    }

    private function fail(string $summary, array $meta = []): array
    {
        return ['status' => 'fail', 'summary' => $summary, 'meta' => $meta];
    }

    private function scoresFromResults(array $resultsByKey): array
    {
        $seoWeights = [
            'page_status' => 5,
            'https' => 4,
            'title_tag' => 4,
            'meta_description' => 4,
            'canonical_url' => 3,
            'viewport_meta' => 2,
            'html_lang' => 2,
            'indexability' => 5,
            'robots_txt' => 3,
            'sitemap_xml' => 3,
            'open_graph_basics' => 3,
            'open_graph_image' => 1,
            'twitter_card' => 1,
        ];

        $aiWeights = [
            'unique_viewpoint' => 8,
            'content_clarity' => 7,
            'media_support' => 4,
            'crawlable_content' => 8,
            'javascript_dependency' => 4,
            'structured_data' => 4,
        ];

        $trustWeights = [
            'privacy_contact' => 8,
            'primary_cta' => 7,
            'security_headers' => 5,
            'compression' => 2,
            'form_labels' => 3,
        ];

        $seoScore = $this->weightedScore($seoWeights, $resultsByKey);
        $aiScore = $this->weightedScore($aiWeights, $resultsByKey);
        $trustScore = $this->weightedScore($trustWeights, $resultsByKey);
        $launchScore = (int) round(($seoScore * 0.4) + ($aiScore * 0.35) + ($trustScore * 0.25));

        return [$seoScore, $aiScore, $trustScore, min(100, $launchScore)];
    }

    private function weightedScore(array $weights, array $resultsByKey): int
    {
        $totalWeight = array_sum($weights);
        $score = 0.0;

        foreach ($weights as $key => $weight) {
            $status = $resultsByKey[$key]['status'] ?? 'warning';
            $multiplier = match ($status) {
                'pass' => 1.0,
                'warning' => 0.55,
                'fail' => 0.0,
                default => 0.4,
            };

            $score += $weight * $multiplier;
        }

        return (int) round(($score / max(1, $totalWeight)) * 100);
    }

    private function statusLabel(int $launchScore): string
    {
        return match (true) {
            $launchScore >= 90 => 'Excellent score',
            $launchScore >= 75 => 'Good score',
            $launchScore >= 55 => 'Fair score',
            default => 'Poor score',
        };
    }

    private function catalog(): array
    {
        return [
            'meta_information' => [
                'label' => 'Meta Information',
                'checks' => [
                    'title_tag' => ['label' => 'Title Tag', 'description' => 'Checks whether the homepage title exists, looks usable, and fits a recommended snippet length.', 'fix' => 'Add a unique <title> tag describing the main page intent in about 30-60 characters.'],
                    'meta_description' => ['label' => 'Meta Description', 'description' => 'Checks for a usable description in search results and previews, including recommended snippet length.', 'fix' => 'Add a concise meta description that explains the page value in about 120-160 characters.'],
                    'canonical_url' => ['label' => 'Canonical URL', 'description' => 'Checks whether the page declares a canonical destination.', 'fix' => 'Add a canonical link pointing to the preferred public URL for this page.'],
                    'favicon' => ['label' => 'Favicon', 'description' => 'Checks whether the page head includes a favicon.', 'fix' => 'Add favicon links in the page head so the site has a branded browser tab and richer previews.'],
                    'viewport_meta' => ['label' => 'Viewport Meta', 'description' => 'Checks for mobile viewport configuration.', 'fix' => 'Add a viewport meta tag such as width=device-width, initial-scale=1 for mobile-friendly rendering.'],
                    'html_lang' => ['label' => 'HTML Lang', 'description' => 'Checks whether the document language is declared.', 'fix' => 'Set the HTML lang attribute to the primary page language, such as en or en-US.'],
                ],
            ],
            'content_structure' => [
                'label' => 'Content Structure',
                'checks' => [
                    'h1_tag' => ['label' => 'H1 Tag', 'description' => 'Checks for a primary heading.', 'fix' => 'Add one clear H1 that states the page topic or value proposition.'],
                    'heading_hierarchy' => ['label' => 'Heading Hierarchy', 'description' => 'Checks whether the page uses multiple heading levels.', 'fix' => 'Organize the page with a single H1 and supporting H2/H3 sections so the content is easier to scan.'],
                    'image_alt_text' => ['label' => 'Image Alt Text', 'description' => 'Checks whether images include alt text coverage.', 'fix' => 'Add short descriptive alt text to meaningful images and keep decorative images empty with alt="".'],
                ],
            ],
            'technical_optimization' => [
                'label' => 'Technical Optimization',
                'checks' => [
                    'page_status' => ['label' => 'Page Status', 'description' => 'Checks whether the submitted page loads successfully.', 'fix' => 'Make sure the page returns a successful response, remove broken redirects, and confirm the URL is publicly reachable.'],
                    'https' => ['label' => 'HTTPS', 'description' => 'Checks whether the final page is served over HTTPS.', 'fix' => 'Enable SSL and force the canonical version of the page to load over HTTPS.'],
                    'https_redirects' => ['label' => 'HTTPS Redirect', 'description' => 'Checks whether the submitted URL resolves cleanly to HTTPS.', 'fix' => 'Redirect all HTTP versions of the page to the matching HTTPS URL with a clean 301 redirect.'],
                    'indexability' => ['label' => 'Indexability', 'description' => 'Checks whether noindex directives are present.', 'fix' => 'Remove noindex directives from production pages that should appear in search and AI discovery.'],
                    'security_headers' => ['label' => 'Security Headers', 'description' => 'Checks for common hardening headers on the response.', 'fix' => 'Add headers such as Strict-Transport-Security, X-Content-Type-Options, Referrer-Policy, and X-Frame-Options where appropriate.'],
                    'compression' => ['label' => 'Compression', 'description' => 'Checks whether the page response appears compressed.', 'fix' => 'Enable Brotli or gzip compression on the web server for HTML, CSS, JS, and other text responses.'],
                    'robots_txt' => ['label' => 'robots.txt', 'description' => 'Checks whether robots.txt is available at the site root.', 'fix' => 'Publish a robots.txt file at the site root and make sure it does not block important launch pages.'],
                    'sitemap_xml' => ['label' => 'Sitemap.xml', 'description' => 'Checks whether sitemap.xml is available at the site root.', 'fix' => 'Publish a sitemap.xml file and include the canonical public pages you want crawled.'],
                ],
            ],
            'accessibility_basics' => [
                'label' => 'Accessibility Basics',
                'checks' => [
                    'form_labels' => ['label' => 'Form Labels', 'description' => 'Checks for labels on visible form controls.', 'fix' => 'Associate every visible form field with a label or accessible aria-label/aria-labelledby text.'],
                    'landmarks' => ['label' => 'Landmarks', 'description' => 'Checks for semantic layout landmarks like main and nav.', 'fix' => 'Use semantic landmarks like header, nav, main, and footer so the page structure is easier to understand.'],
                ],
            ],
            'social_and_rich_results' => [
                'label' => 'Social & Rich Results',
                'checks' => [
                    'open_graph_basics' => ['label' => 'Open Graph Basics', 'description' => 'Checks for core social-sharing metadata, including recommended title and description lengths.', 'fix' => 'Add og:title, og:description, and og:type tags, and keep og:title around 30-60 characters and og:description around 120-160 characters.'],
                    'open_graph_image' => ['label' => 'Open Graph Image', 'description' => 'Checks whether a social preview image is present.', 'fix' => 'Add a branded og:image that looks good when the page is shared on social platforms or chat apps.'],
                    'twitter_card' => ['label' => 'Twitter Card', 'description' => 'Checks whether a Twitter card tag is present.', 'fix' => 'Add a twitter:card tag, ideally summary_large_image if you have a preview image.'],
                    'structured_data' => ['label' => 'Structured Data', 'description' => 'Checks whether JSON-LD markup is present.', 'fix' => 'Add relevant JSON-LD schema markup that matches the page content, such as Organization, WebSite, Product, or FAQPage when appropriate.'],
                ],
            ],
            'links_analysis' => [
                'label' => 'Links Analysis',
                'checks' => [
                    'internal_links' => ['label' => 'Internal Links', 'description' => 'Checks whether the page links deeper into the site.', 'fix' => 'Add internal links to important pages like pricing, docs, features, contact, or product detail sections.'],
                    'external_links' => ['label' => 'External Links', 'description' => 'Checks whether the page links out when relevant.', 'fix' => 'Link out where it adds trust or context, such as app stores, documentation, status pages, or social proof sources.'],
                    'privacy_contact' => ['label' => 'Trust Links', 'description' => 'Checks for privacy, contact, support, or similar trust paths.', 'fix' => 'Add visible links to privacy, contact, support, about, or security pages to strengthen trust signals.'],
                ],
            ],
            'ai_and_launch_signals' => [
                'label' => 'AI & Launch Signals',
                'checks' => [
                    'primary_cta' => ['label' => 'Primary CTA', 'description' => 'Checks whether the page has a clear call to action.', 'fix' => 'Add one obvious primary CTA near the top of the page, such as Get started, Book demo, or Try it free.'],
                    'unique_viewpoint' => ['label' => 'Unique Viewpoint', 'description' => 'Checks for first-hand or differentiated messaging signals.', 'fix' => 'Add first-hand insight, proof, customer examples, or founder perspective so the page does not read like generic marketing copy.'],
                    'content_clarity' => ['label' => 'Content Clarity', 'description' => 'Checks whether the page has enough readable structure and depth.', 'fix' => 'Break the page into clearer sections with supporting copy, headings, and direct explanations of what the product does.'],
                    'media_support' => ['label' => 'Media Support', 'description' => 'Checks whether the page uses supporting imagery or video.', 'fix' => 'Add screenshots, product visuals, or a short explainer video that helps visitors understand the product faster.'],
                    'crawlable_content' => ['label' => 'Crawlable Content', 'description' => 'Checks whether useful text is present in the HTML response.', 'fix' => 'Make sure the main page copy is present in the server-rendered HTML and not only injected after JavaScript loads.'],
                    'javascript_dependency' => ['label' => 'JavaScript Dependency', 'description' => 'Checks whether the page looks too dependent on JS for visible content.', 'fix' => 'Reduce reliance on client-side rendering for key homepage content or add server-side rendering/prerendering for the important sections.'],
                ],
            ],
        ];
    }
}
