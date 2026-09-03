<?php

namespace App\Services;

use App\Models\AiCrawlerDailyStat;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HeaderStatsService
{
    public const CACHE_KEY = 'site.header_stats.v1';

    public function get(): array
    {
        return Cache::remember(self::CACHE_KEY, config('ai_crawlers.header_cache_seconds', 900), function () {
            $productStats = Product::query()
                ->selectRaw('COUNT(*) as submitted_products, COALESCE(SUM(outbound_clicks_count), 0) as product_clicks')
                ->first();

            return [
                'submitted_products' => (int) ($productStats->submitted_products ?? 0),
                'product_clicks' => (int) ($productStats->product_clicks ?? 0),
                'ai_bot_requests' => Schema::hasTable('ai_crawler_daily_stats')
                    ? (int) AiCrawlerDailyStat::query()->sum('requests')
                    : 0,
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
