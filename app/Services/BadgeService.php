<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Product;
use Carbon\Carbon;

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
        $badge = Badge::first();

        if ($badge && $badge->path) {
            return asset('storage/' . $badge->path);
        }

        return url('/images/badge.png');
    }

    public function getBadgeDestinationUrl(): string
    {
        return url('/');
    }

    public function getEmbedData(): array
    {
        $badgeImageUrl = $this->getBadgeImageUrl();
        $destinationUrl = $this->getBadgeDestinationUrl();
        $altText = 'Featured on Software on the Web';

        return [
            'snippet' => '<a href="' . $destinationUrl . '" rel="dofollow">' . "\n"
                . '  <img src="' . $badgeImageUrl . '" alt="' . $altText . '" width="200">' . "\n"
                . '</a>',
            'badge_image_url' => $badgeImageUrl,
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

        Badge::query()->pluck('path')->each(function ($path) use (&$paths) {
            $resolvedPath = parse_url(url($path), PHP_URL_PATH);
            if ($resolvedPath) {
                $paths[] = $resolvedPath;
            }
        });

        return array_values(array_unique(array_filter($paths)));
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
