<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Product;
use App\Support\PublicUrlGuard;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class BadgeService
{
    /**
     * Generate the HTML badge snippet for a product.
     */
    public function generateSnippet(Product $product): string
    {
        return $this->getEmbedData()['snippet'];
    }

    /**
     * Get the badge image URL (from the database or fallback to static asset).
     */
    public function getBadgeImageUrl(): string
    {
        $settingsBadgeImageUrls = $this->getSettingsBadgeImageUrls();
        $preferredSettingsBadgeUrl = $settingsBadgeImageUrls['svg'] ?? $settingsBadgeImageUrls['png'] ?? $settingsBadgeImageUrls['legacy'];

        if ($preferredSettingsBadgeUrl) {
            return $preferredSettingsBadgeUrl;
        }

        $badge = Badge::first();

        if ($badge && $badge->path) {
            return asset('storage/'.$badge->path);
        }

        return $this->appendCacheBustToPublicImageUrl(url('/images/badge.png'));
    }

    public function getBadgeDestinationUrl(): string
    {
        return url('/');
    }

    public function getEmbedData(): array
    {
        $badgeImageUrl = $this->getBadgeImageUrl();
        $badgeImageSvgUrl = $this->getBadgeSvgUrl();
        $badgeImagePngUrl = $this->getBadgePngUrl();
        $badgeImageWebpUrl = $this->getBadgeWebpUrl();
        $destinationUrl = $this->getBadgeDestinationUrl();

        return [
            'snippet' => $this->getBadgeEmbedCode($destinationUrl, $badgeImageSvgUrl, $badgeImagePngUrl, $badgeImageUrl),
            'badge_image_url' => $badgeImageUrl,
            'badge_image_svg_url' => $badgeImageSvgUrl,
            'badge_image_png_url' => $badgeImagePngUrl,
            'badge_image_webp_url' => $badgeImageWebpUrl,
            'destination_url' => $destinationUrl,
        ];
    }

    /**
     * Calculate the next Monday at 7:00 UTC.
     * If today IS Monday and it's before 7:00 UTC, returns today at 7:00 UTC.
     * Otherwise, returns the following Monday at 7:00 UTC.
     */
    public function getNextMondayLaunchDate(): Carbon
    {
        $now = Carbon::now('UTC');

        // If today is Monday and before 7:00 UTC, launch today
        if ($now->isMonday() && $now->hour < 7) {
            return $now->copy()->setTime(7, 0, 0);
        }

        // Otherwise, next Monday at 7:00 UTC
        return $now->copy()->next(Carbon::MONDAY)->setTime(7, 0, 0);
    }

    public function getEarliestBadgeLaunchDate(): Carbon
    {
        return Carbon::now('UTC')->next(Carbon::MONDAY)->setTime(7, 0, 0);
    }

    public function resolveBadgeLaunchDate(string $launchDateValue): Carbon
    {
        $launchDate = Carbon::createFromFormat('Y-m-d', $launchDateValue, 'UTC')->setTime(7, 0, 0);

        if ($launchDate->lt($this->getEarliestBadgeLaunchDate())) {
            throw new \InvalidArgumentException('Please choose a launch date starting from next Monday.');
        }

        if ($launchDate->gt(Carbon::now('UTC')->addDays(365)->endOfDay())) {
            throw new \InvalidArgumentException('Please choose a launch date within the next 365 days.');
        }

        return $launchDate;
    }

    public function verifyPlacementUrl(string $url): array
    {
        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl($url);
        } catch (\InvalidArgumentException $e) {
            return [
                'verified' => false,
                'reason' => 'invalid_url',
                'message' => 'We could not check your badge because its saved placement URL is invalid or does not point to a public website. Please update the badge placement URL.',
            ];
        }

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => $userAgents[array_rand($userAgents)],
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(15)->get($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'verified' => false,
                'reason' => 'connection_failed',
                'message' => "We could not connect to {$url} to check your badge. Please check that your website is online and publicly accessible.",
            ];
        }

        if ($response->failed()) {
            return [
                'verified' => false,
                'reason' => 'http_error',
                'http_status' => $response->status(),
                'response_hash' => hash('sha256', $response->body()),
                'message' => "Your badge page at {$url} returned HTTP {$response->status()}. We could not check the badge. Please ensure the page is publicly accessible and allows verification requests.",
            ];
        }

        $html = $response->body();
        $evidence = [
            'http_status' => $response->status(),
            'response_hash' => hash('sha256', $html),
        ];
        $doc = new \DOMDocument;
        @$doc->loadHTML($html);

        $links = $doc->getElementsByTagName('a');
        $expectedHost = parse_url($this->getBadgeDestinationUrl(), PHP_URL_HOST);
        $allowedBadgePaths = $this->getAllowedBadgeImagePaths();

        $hasNofollowBadge = false;
        foreach ($links as $link) {
            $href = trim($link->getAttribute('href'));
            $linkHost = parse_url($href, PHP_URL_HOST);

            if (! $href || ! $linkHost || strcasecmp($linkHost, $expectedHost) !== 0) {
                continue;
            }

            $rel = strtolower($link->getAttribute('rel'));
            if (in_array('nofollow', preg_split('/\\s+/', trim($rel)), true)) {
                $hasNofollowBadge = $hasNofollowBadge || $this->linkContainsExpectedBadgeImage($link, $allowedBadgePaths);

                continue;
            }

            if ($this->linkContainsExpectedBadgeImage($link, $allowedBadgePaths)) {
                return [
                    'verified' => true,
                    ...$evidence,
                    'matched_element' => $doc->saveHTML($link) ?: null,
                    'message' => 'Badge verified. You can now choose your launch date.',
                ];
            }
        }

        $hasBadgeImage = $this->linkContainsExpectedBadgeImage($doc->documentElement, $allowedBadgePaths, false);
        $destination = $this->getBadgeDestinationUrl();
        if ($hasNofollowBadge) {
            $reason = 'nofollow';
            $message = "Your badge link on {$url} contains rel=\"nofollow\". Verification requires a link without nofollow. Remove only nofollow; you can keep noopener and noreferrer.";
        } elseif ($hasBadgeImage) {
            $reason = 'incorrect_backlink';
            $message = "We found your badge on {$url}, but it does not link to {$destination}. Please wrap the badge image in a link to {$destination}.";
        } else {
            $reason = 'badge_missing';
            $message = "We could not find the Software on the Web badge in the HTML returned by {$url}. Please add the badge using the embed code below. If it is already visible, ensure it appears in the page’s initial HTML rather than being added only through JavaScript.";
        }

        return [
            'verified' => false,
            ...$evidence,
            'reason' => $reason,
            'message' => $message,
        ];
    }

    /**
     * Get a human-readable launch date string.
     */
    public function getLaunchDateFormatted(Carbon $date): string
    {
        return $date->format('l, F j, Y').' at 7:00 AM UTC';
    }

    private function getAllowedBadgeImagePaths(): array
    {
        $paths = [];

        $paths[] = parse_url($this->getBadgeImageUrl(), PHP_URL_PATH);
        $paths[] = parse_url($this->getBadgeSvgUrl() ?? '', PHP_URL_PATH);
        $paths[] = parse_url($this->getBadgePngUrl() ?? '', PHP_URL_PATH);
        $paths[] = parse_url($this->getBadgeWebpUrl() ?? '', PHP_URL_PATH);

        Badge::query()->pluck('path')->each(function ($path) use (&$paths) {
            $resolvedPath = parse_url(url($path), PHP_URL_PATH);
            if ($resolvedPath) {
                $paths[] = $resolvedPath;
            }
        });

        return array_values(array_unique(array_filter($paths)));
    }

    private function getBadgeSvgUrl(): ?string
    {
        $settingsBadgeImageUrls = $this->getSettingsBadgeImageUrls();

        return $settingsBadgeImageUrls['svg'] ?? null;
    }

    private function getBadgePngUrl(): ?string
    {
        $settingsBadgeImageUrls = $this->getSettingsBadgeImageUrls();

        return $settingsBadgeImageUrls['png'] ?? null;
    }

    private function getBadgeWebpUrl(): ?string
    {
        $settingsBadgeImageUrls = $this->getSettingsBadgeImageUrls();

        return $settingsBadgeImageUrls['webp'] ?? null;
    }

    private function getSettingsBadgeImageUrls(): array
    {
        if (! Storage::disk('local')->exists('settings.json')) {
            return [
                'svg' => null,
                'png' => null,
                'webp' => $this->normalizeBadgeAssetUrl($this->publicBadgeAssetUrlIfExists('badge.webp')),
                'legacy' => null,
            ];
        }

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        $badgeImageSvgUrl = $this->normalizeBadgeAssetUrl($settings['badge_image_svg_url'] ?? null);
        $badgeImagePngUrl = $this->normalizeBadgeAssetUrl($settings['badge_image_png_url'] ?? null);
        $badgeImageWebpUrl = $this->normalizeBadgeAssetUrl($settings['badge_image_webp_url'] ?? null);
        $legacyBadgeImageUrl = $this->normalizeBadgeAssetUrl($settings['badge_image_url'] ?? null);

        if (! $badgeImageSvgUrl && $legacyBadgeImageUrl && str_ends_with(strtolower(parse_url($legacyBadgeImageUrl, PHP_URL_PATH) ?? ''), '.svg')) {
            $badgeImageSvgUrl = $legacyBadgeImageUrl;
        }

        if (! $badgeImagePngUrl && $legacyBadgeImageUrl && str_ends_with(strtolower(parse_url($legacyBadgeImageUrl, PHP_URL_PATH) ?? ''), '.png')) {
            $badgeImagePngUrl = $legacyBadgeImageUrl;
        }

        if (! $badgeImageWebpUrl) {
            $badgeImageWebpUrl = $this->normalizeBadgeAssetUrl($this->publicBadgeAssetUrlIfExists('badge.webp'));
        }

        if (! $badgeImageWebpUrl && $legacyBadgeImageUrl && str_ends_with(strtolower(parse_url($legacyBadgeImageUrl, PHP_URL_PATH) ?? ''), '.webp')) {
            $badgeImageWebpUrl = $legacyBadgeImageUrl;
        }

        return [
            'svg' => $badgeImageSvgUrl,
            'png' => $badgeImagePngUrl,
            'webp' => $badgeImageWebpUrl,
            'legacy' => $legacyBadgeImageUrl,
        ];
    }

    private function publicBadgeAssetUrlIfExists(string $filename): ?string
    {
        $path = public_path('images/'.$filename);

        return is_file($path) ? url('/images/'.$filename) : null;
    }

    private function normalizeBadgeAssetUrl(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        return $this->appendCacheBustToPublicImageUrl(trim($url));
    }

    private function getBadgeEmbedCode(
        ?string $destinationUrl = null,
        ?string $badgeImageSvgUrl = null,
        ?string $badgeImagePngUrl = null,
        ?string $badgeImageUrl = null
    ): string {
        $savedBadgeEmbedCode = $this->getSavedBadgeEmbedCode();

        if ($savedBadgeEmbedCode) {
            return $savedBadgeEmbedCode;
        }

        return $this->buildDefaultBadgeEmbedCode(
            $destinationUrl ?? $this->getBadgeDestinationUrl(),
            $badgeImageSvgUrl ?? $this->getBadgeSvgUrl(),
            $badgeImagePngUrl ?? $this->getBadgePngUrl(),
            $badgeImageUrl ?? $this->getBadgeImageUrl()
        );
    }

    private function getSavedBadgeEmbedCode(): ?string
    {
        if (! Storage::disk('local')->exists('settings.json')) {
            return null;
        }

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        $badgeEmbedCode = trim((string) ($settings['badge_embed_code'] ?? ''));

        return $badgeEmbedCode !== '' ? $badgeEmbedCode : null;
    }

    private function buildDefaultBadgeEmbedCode(
        string $destinationUrl,
        ?string $badgeImageSvgUrl,
        ?string $badgeImagePngUrl,
        string $badgeImageUrl
    ): string {
        $altText = 'Featured on Software on the Web';

        if ($badgeImageSvgUrl && $badgeImagePngUrl) {
            return '<a href="'.$destinationUrl.'" rel="dofollow">'."\n"
                .'  <picture>'."\n"
                .'    <source srcset="'.$badgeImageSvgUrl.'" type="image/svg+xml">'."\n"
                .'    <img src="'.$badgeImagePngUrl.'" alt="'.$altText.'" width="200">'."\n"
                .'  </picture>'."\n"
                .'</a>';
        }

        return '<a href="'.$destinationUrl.'" rel="dofollow">'."\n"
            .'  <img src="'.$badgeImageUrl.'" alt="'.$altText.'" width="200">'."\n"
            .'</a>';
    }

    private function appendCacheBustToPublicImageUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! $path) {
            return $url;
        }

        $publicFilePath = public_path(ltrim($path, '/'));
        if (! is_file($publicFilePath)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'v='.filemtime($publicFilePath);
    }

    private function linkContainsExpectedBadgeImage(?\DOMElement $link, array $allowedBadgePaths, bool $allowGenericBadge = true): bool
    {
        if (! $link) {
            return false;
        }

        foreach ($link->getElementsByTagName('img') as $image) {
            $src = trim($image->getAttribute('src'));
            $srcPath = parse_url($src, PHP_URL_PATH);
            $alt = strtolower(trim($image->getAttribute('alt')));

            if ($srcPath && in_array($srcPath, $allowedBadgePaths, true)) {
                return true;
            }

            if (str_contains($alt, 'software on the web') || ($allowGenericBadge && str_contains(strtolower($srcPath ?? ''), 'badge'))) {
                return true;
            }
        }

        return false;
    }
}
