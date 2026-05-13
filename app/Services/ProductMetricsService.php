<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductLaunchStat;
use App\Models\ProductWeekStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductMetricsService
{
    public function recordListImpressions(iterable $productIds, ?Carbon $occurredAt = null): void
    {
        $ids = collect($productIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        Product::query()
            ->whereIn('id', $ids)
            ->where('approved', true)
            ->where('is_published', true)
            ->get()
            ->each(fn (Product $product) => $this->incrementMetrics($product, ['list_impressions' => 1], $occurredAt));
    }

    public function recordDetailView(Product $product, ?Carbon $occurredAt = null): void
    {
        if (! $product->approved || ! $product->is_published) {
            return;
        }

        $this->incrementMetrics($product, ['detail_views' => 1], $occurredAt);
    }

    public function recordOutboundClick(Product $product, ?Carbon $occurredAt = null): void
    {
        if (! $product->approved || ! $product->is_published) {
            return;
        }

        $this->incrementMetrics($product, ['outbound_clicks' => 1], $occurredAt);
    }

    public function recordManualUpvote(Product $product, int $delta, ?Carbon $occurredAt = null): void
    {
        if (! $product->approved || ! $product->is_published || $delta === 0) {
            return;
        }

        $this->incrementMetrics($product, ['manual_upvotes' => $delta], $occurredAt);
    }

    protected function incrementMetrics(Product $product, array $deltas, ?Carbon $occurredAt = null): void
    {
        $at = ($occurredAt ?? now())->copy();

        DB::transaction(function () use ($product, $deltas, $at) {
            $weekStat = $this->resolveWeekStat($product);
            $this->applyDeltas($weekStat, $deltas);
            $weekStat->ranking_score = ProductRankingService::calculateWeeklyScore($weekStat);
            $weekStat->save();

            if ($product->isInLaunchWindow($at)) {
                $launchStat = $this->resolveLaunchStat($product);
                $this->applyDeltas($launchStat, $deltas);
                $launchStat->exploration_score = ProductRankingService::calculateExplorationScore($launchStat);
                $launchStat->save();
            }
        });

        foreach ($product->rankingCacheKeys() as $cacheKey) {
            cache()->forget($cacheKey);
        }
    }

    protected function resolveWeekStat(Product $product): ProductWeekStat
    {
        return ProductWeekStat::firstOrNew([
            'product_id' => $product->getKey(),
            'week_start' => $product->rankingWeekStart()->toDateString(),
        ]);
    }

    protected function resolveLaunchStat(Product $product): ProductLaunchStat
    {
        $launchWindowStart = $product->launchWindowStart();
        $launchWindowEnd = $product->launchWindowEnd();

        return ProductLaunchStat::firstOrNew(['product_id' => $product->getKey()], [
            'launch_window_start' => $launchWindowStart,
            'launch_window_end' => $launchWindowEnd,
        ]);
    }

    protected function applyDeltas(ProductWeekStat|ProductLaunchStat $stat, array $deltas): void
    {
        foreach ($deltas as $field => $delta) {
            $current = (int) ($stat->{$field} ?? 0);
            $stat->{$field} = max(0, $current + (int) $delta);
        }
    }
}
