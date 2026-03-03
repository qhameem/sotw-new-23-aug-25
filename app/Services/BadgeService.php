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
        $productUrl = url('/product/' . $product->slug);
        $badgeImageUrl = $this->getBadgeImageUrl();
        $altText = 'Featured on Software on the Web';

        return '<a href="' . $productUrl . '" rel="dofollow">'
            . '<img src="' . $badgeImageUrl . '" alt="' . $altText . '" width="200">'
            . '</a>';
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

    /**
     * Get a human-readable launch date string.
     */
    public function getLaunchDateFormatted(Carbon $date): string
    {
        return $date->format('l, F j, Y') . ' at 7:00 AM UTC';
    }
}
