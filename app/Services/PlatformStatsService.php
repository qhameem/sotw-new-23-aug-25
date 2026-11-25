<?php

namespace App\Services;

use App\Models\AiCrawlerDailyStat;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductWeekStat;
use App\Models\StartupDirectory;
use App\Models\TechStack;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PlatformStatsService
{
    public function __construct(
        private HeaderStatsService $header,
        private ProductRankingService $ranking,
    ) {}

    public function get(): array
    {
        $week = now()->startOfWeek(Carbon::MONDAY);

        return Cache::remember('site.platform_stats.v3:'.$week->toDateString(), now()->addMinutes(15), function () use ($week) {
            $previousWeek = $week->copy()->subWeek();
            $firstWeek = $week->copy()->subWeeks(11);
            $activity = ProductWeekStat::query()
                ->whereBetween('week_start', [$firstWeek->toDateString(), $week->toDateString()])
                ->whereHas('product', fn ($query) => $query->approvedAndPublished())
                ->selectRaw('week_start, SUM(outbound_clicks) as clicks')
                ->groupBy('week_start')
                ->get()
                ->keyBy(fn (ProductWeekStat $stat) => $stat->week_start->toDateString());

            $totals = $this->header->get();
            $aiRequests = $totals['total_ai_reads'] === null
                ? collect()
                : AiCrawlerDailyStat::query()
                    ->whereBetween('visited_on', [$firstWeek->toDateString(), today()->toDateString()])
                    ->selectRaw('visited_on, SUM(requests) as requests')
                    ->groupBy('visited_on')
                    ->get()
                    ->groupBy(fn (AiCrawlerDailyStat $stat) => $stat->visited_on->startOfWeek(Carbon::MONDAY)->toDateString())
                    ->map(fn ($days) => (int) $days->sum('requests'));

            $weekly = collect(range(0, 11))->map(function (int $offset) use ($firstWeek, $activity, $aiRequests) {
                $start = $firstWeek->copy()->addWeeks($offset);
                $totals = $activity->get($start->toDateString());

                return [
                    'label' => $start->format('M j'),
                    'launches' => Product::approvedAndPublished()
                        ->effectivePublishedBetween($start, $start->copy()->endOfWeek(Carbon::SUNDAY))
                        ->count(),
                    'clicks' => (int) ($totals?->clicks ?? 0),
                    'ai_requests' => $aiRequests->get($start->toDateString(), 0),
                ];
            });

            $rankedIds = $this->ranking->organicWeekProductIds($previousWeek, $previousWeek->copy()->endOfWeek(Carbon::SUNDAY))->take(3);
            $products = Product::approvedAndPublished()->whereIn('id', $rankedIds)->get(['id', 'name', 'slug'])->keyBy('id');
            $directories = StartupDirectory::query()
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN pricing IN ('free', 'freemium') THEN 1 ELSE 0 END) as free_options")
                ->selectRaw("SUM(CASE WHEN link_type = 'dofollow' THEN 1 ELSE 0 END) as dofollow")
                ->first();

            return [
                'totals' => $totals,
                'weekly' => $weekly,
                'leaders' => $rankedIds->map(fn ($id) => $products->get($id))->filter()->values(),
                'week' => $week,
                'previousWeek' => $previousWeek,
                'categories' => Category::submittable()->with('types:id,name')
                    ->withCount(['products' => fn ($query) => $query->approvedAndPublished()])
                    ->whereHas('products', fn ($query) => $query->approvedAndPublished())
                    ->orderByDesc('products_count')->orderBy('name')->limit(3)->get(),
                'techStacks' => TechStack::query()
                    ->withCount(['products' => fn ($query) => $query->approvedAndPublished()])
                    ->whereHas('products', fn ($query) => $query->approvedAndPublished())
                    ->orderByDesc('products_count')->orderBy('name')->limit(3)->get(),
                'directories' => [
                    'Listed directories' => (int) $directories->total,
                    'Free or freemium' => (int) $directories->free_options,
                    'Dofollow' => (int) $directories->dofollow,
                    'Median Domain Rating' => StartupDirectory::whereNotNull('dr')->pluck('dr')->median(),
                ],
                'updatedAt' => now(),
            ];
        });
    }
}
