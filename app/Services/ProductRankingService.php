<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductLaunchStat;
use App\Models\ProductWeekStat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProductRankingService
{
    public function organicDateProductIds(Carbon $date): Collection
    {
        return $this->organicPeriodProductIds($date->copy()->startOfDay(), $date->copy()->endOfDay(), 'date');
    }

    public function organicWeekProductIds(Carbon $startOfWeek, Carbon $endOfWeek): Collection
    {
        return $this->organicPeriodProductIds($startOfWeek, $endOfWeek, 'week');
    }

    public function organicMonthProductIds(Carbon $startOfMonth, Carbon $endOfMonth): Collection
    {
        return $this->organicPeriodProductIds($startOfMonth, $endOfMonth, 'month');
    }

    public function organicYearProductIds(Carbon $startOfYear, Carbon $endOfYear): Collection
    {
        return $this->organicPeriodProductIds($startOfYear, $endOfYear, 'year');
    }

    public static function calculateWeeklyScore(ProductWeekStat $weekStat): float
    {
        $manualUpvotes = (int) ($weekStat->manual_upvotes ?? 0);
        $outboundClicks = (int) ($weekStat->outbound_clicks ?? 0);
        $detailViews = (int) ($weekStat->detail_views ?? 0);
        $listImpressions = (int) ($weekStat->list_impressions ?? 0);

        return round(
            ($manualUpvotes * 10)
            + (sqrt($outboundClicks) * 4)
            + (sqrt($detailViews) * 1.5)
            + (sqrt($listImpressions) * 0.5),
            4
        );
    }

    public static function calculateExplorationScore(ProductLaunchStat $launchStat): float
    {
        $manualUpvotes = (int) ($launchStat->manual_upvotes ?? 0);
        $outboundClicks = (int) ($launchStat->outbound_clicks ?? 0);
        $detailViews = (int) ($launchStat->detail_views ?? 0);
        $listImpressions = max(1, (int) ($launchStat->list_impressions ?? 0));

        return round(
            (($manualUpvotes * 8) + ($outboundClicks * 3) + ($detailViews * 2)) / $listImpressions,
            4
        );
    }

    protected function organicPeriodProductIds(Carbon $start, Carbon $end, string $periodType): Collection
    {
        return cache()->remember(
            $this->cacheKey($start, $periodType),
            now()->addMinutes(10),
            fn () => $this->buildOrganicPeriodProductIds($start, $end)
        );
    }

    protected function buildOrganicPeriodProductIds(Carbon $start, Carbon $end): Collection
    {
        $periodWeekStarts = $this->weekStartsForPeriod($start, $end);

        $products = Product::query()
            ->with([
                'weekStats' => fn ($query) => $query->whereIn('week_start', $periodWeekStarts),
                'launchStat',
            ])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->whereBetween(\DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->get();

        $launchProducts = $products
            ->filter(fn (Product $product) => $product->isInLaunchWindow())
            ->sort(function (Product $left, Product $right) {
                $leftImpressions = (int) ($left->launchStat?->list_impressions ?? 0);
                $rightImpressions = (int) ($right->launchStat?->list_impressions ?? 0);

                if ($leftImpressions !== $rightImpressions) {
                    return $leftImpressions <=> $rightImpressions;
                }

                $leftManual = (int) ($left->launchStat?->manual_upvotes ?? 0);
                $rightManual = (int) ($right->launchStat?->manual_upvotes ?? 0);

                if ($leftManual !== $rightManual) {
                    return $rightManual <=> $leftManual;
                }

                $leftClicks = (int) ($left->launchStat?->outbound_clicks ?? 0);
                $rightClicks = (int) ($right->launchStat?->outbound_clicks ?? 0);

                if ($leftClicks !== $rightClicks) {
                    return $rightClicks <=> $leftClicks;
                }

                $leftPublishedAt = $left->published_at?->timestamp ?? $left->created_at?->timestamp ?? PHP_INT_MAX;
                $rightPublishedAt = $right->published_at?->timestamp ?? $right->created_at?->timestamp ?? PHP_INT_MAX;

                if ($leftPublishedAt !== $rightPublishedAt) {
                    return $leftPublishedAt <=> $rightPublishedAt;
                }

                return $left->id <=> $right->id;
            })
            ->values();

        $matureProducts = $products
            ->reject(fn (Product $product) => $product->isInLaunchWindow())
            ->sort(function (Product $left, Product $right) {
                $leftScore = (float) ($left->weekStats->sum('ranking_score') ?? 0.0);
                $rightScore = (float) ($right->weekStats->sum('ranking_score') ?? 0.0);

                if ($leftScore !== $rightScore) {
                    return $rightScore <=> $leftScore;
                }

                $leftManual = (int) $left->weekStats->sum('manual_upvotes');
                $rightManual = (int) $right->weekStats->sum('manual_upvotes');

                if ($leftManual !== $rightManual) {
                    return $rightManual <=> $leftManual;
                }

                $leftPublishedAt = $left->published_at?->timestamp ?? $left->created_at?->timestamp ?? PHP_INT_MAX;
                $rightPublishedAt = $right->published_at?->timestamp ?? $right->created_at?->timestamp ?? PHP_INT_MAX;

                if ($leftPublishedAt !== $rightPublishedAt) {
                    return $leftPublishedAt <=> $rightPublishedAt;
                }

                return $left->id <=> $right->id;
            })
            ->values();

        $matureIds = $matureProducts->pluck('id')->all();
        $launchIds = $launchProducts->pluck('id')->all();

        foreach ($launchIds as $index => $launchId) {
            $insertAt = min($index * 3, count($matureIds));
            array_splice($matureIds, $insertAt, 0, [$launchId]);
        }

        return collect($matureIds);
    }

    protected function cacheKey(Carbon $start, string $periodType): string
    {
        return 'ranking:' . $periodType . ':' . $start->toDateString() . ':organic';
    }

    protected function weekStartsForPeriod(Carbon $start, Carbon $end): array
    {
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $last = $end->copy()->startOfWeek(Carbon::MONDAY);
        $weekStarts = [];

        while ($cursor->lte($last)) {
            $weekStarts[] = $cursor->toDateString();
            $cursor->addWeek();
        }

        return $weekStarts;
    }
}
