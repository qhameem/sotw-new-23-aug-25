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
            return asset('storage/' . $badge->path);
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

    public function resolveBadgeLaunchDate(string $weekStart): Carbon
    {
        $launchDate = Carbon::createFromFormat('Y-m-d', $weekStart, 'UTC')->setTime(7, 0, 0);

        if (!$launchDate->isMonday()) {
            throw new \InvalidArgumentException('Launch week must start on a Monday.');
        }

        if ($launchDate->lt($this->getEarliestBadgeLaunchDate())) {
            throw new \InvalidArgumentException('Please choose a week starting from next Monday.');
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
                'message' => $e->getMessage(),
            ];
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->timeout(15)->get($url);

        if ($response->failed()) {
            return [
                'verified' => false,
                'message' => "We couldn't reach that page right now (HTTP {$response->status()}).",
            ];
        }

        $html = $response->body();
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $links = $doc->getElementsByTagName('a');
        $expectedHost = parse_url($this->getBadgeDestinationUrl(), PHP_URL_HOST);
        $allowedBadgePaths = $this->getAllowedBadgeImagePaths();

        foreach ($links as $link) {
            $href = trim($link->getAttribute('href'));
            $linkHost = parse_url($href, PHP_URL_HOST);

            if (!$href || !$linkHost || strcasecmp($linkHost, $expectedHost) !== 0) {
                continue;
            }

            $rel = strtolower($link->getAttribute('rel'));
            if (str_contains($rel, 'nofollow')) {
                continue;
            }

            if ($this->linkContainsExpectedBadgeImage($link, $allowedBadgePaths)) {
                return [
                    'verified' => true,
                    'message' => 'Badge verified. You can now choose your launch week.',
                ];
            }
        }

        return [
            'verified' => false,
            'message' => 'We found the page, but not a dofollow Software on the Web badge yet.',
        ];
    }

    /**
     * Get a human-readable launch date string.
     */
    public function getLaunchDateFormatted(Carbon $date): string
    {
        return $date->format('l, F j, Y') . ' at 7:00 AM UTC';
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
        if (!Storage::disk('local')->exists('settings.json')) {
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

        if (!$badgeImageSvgUrl && $legacyBadgeImageUrl && str_ends_with(strtolower(parse_url($legacyBadgeImageUrl, PHP_URL_PATH) ?? ''), '.svg')) {
            $badgeImageSvgUrl = $legacyBadgeImageUrl;
        }

        if (!$badgeImagePngUrl && $legacyBadgeImageUrl && str_ends_with(strtolower(parse_url($legacyBadgeImageUrl, PHP_URL_PATH) ?? ''), '.png')) {
            $badgeImagePngUrl = $legacyBadgeImageUrl;
        }

        if (!$badgeImageWebpUrl) {
            $badgeImageWebpUrl = $this->normalizeBadgeAssetUrl($this->publicBadgeAssetUrlIfExists('badge.webp'));
        }

        if (!$badgeImageWebpUrl && $legacyBadgeImageUrl && str_ends_with(strtolower(parse_url($legacyBadgeImageUrl, PHP_URL_PATH) ?? ''), '.webp')) {
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
        $path = public_path('images/' . $filename);

        return is_file($path) ? url('/images/' . $filename) : null;
    }

    private function normalizeBadgeAssetUrl(mixed $url): ?string
    {
        if (!is_string($url) || trim($url) === '') {
            return null;
        }

        return $this->appendCacheBustToPublicImageUrl(trim($url));
    }

    private function getBadgeEmbedCode(
        ?string $destinationUrl = null,
        ?string $badgeImageSvgUrl = null,
        ?string $badgeImagePngUrl = null,
        ?string $badgeImageUrl = null
    ): string
    {
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
        if (!Storage::disk('local')->exists('settings.json')) {
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
    ): string
    {
        $altText = 'Featured on Software on the Web';

        if ($badgeImageSvgUrl && $badgeImagePngUrl) {
            return '<a href="' . $destinationUrl . '" rel="dofollow">' . "\n"
                . '  <picture>' . "\n"
                . '    <source srcset="' . $badgeImageSvgUrl . '" type="image/svg+xml">' . "\n"
                . '    <img src="' . $badgeImagePngUrl . '" alt="' . $altText . '" width="200">' . "\n"
                . '  </picture>' . "\n"
                . '</a>';
        }

        return '<a href="' . $destinationUrl . '" rel="dofollow">' . "\n"
            . '  <img src="' . $badgeImageUrl . '" alt="' . $altText . '" width="200">' . "\n"
            . '</a>';
    }

    private function appendCacheBustToPublicImageUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return $url;
        }

        $publicFilePath = public_path(ltrim($path, '/'));
        if (!is_file($publicFilePath)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'v=' . filemtime($publicFilePath);
    }

    private function linkContainsExpectedBadgeImage(\DOMElement $link, array $allowedBadgePaths): bool
    {
        foreach ($link->getElementsByTagName('img') as $image) {
            $src = trim($image->getAttribute('src'));
            $srcPath = parse_url($src, PHP_URL_PATH);
            $alt = strtolower(trim($image->getAttribute('alt')));

            if ($srcPath && in_array($srcPath, $allowedBadgePaths, true)) {
                return true;
            }

            if (str_contains($alt, 'software on the web') || str_contains(strtolower($srcPath ?? ''), 'badge')) {
                return true;
            }
        }

        return false;
    }
}
