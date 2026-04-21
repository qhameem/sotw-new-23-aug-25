<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdZone;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdDeliveryService
{
    public function __construct(
        protected CodeSnippetVisibilityService $visibilityService,
    ) {
    }

    public function contextFromRequest(?Request $request = null, array $overrides = []): array
    {
        $request ??= request();

        return array_merge(array_filter([
            'request' => $request,
            'country_code' => $request ? $this->visibilityService->resolveCountryCode($request) : null,
            'route_name' => $request?->route()?->getName(),
            'page_type' => $request?->route()?->getName(),
            'category_id' => null,
            'audience_scope' => $request?->user() ? 'authenticated' : 'guest',
            'device_type' => $this->detectDeviceType($request),
        ], fn ($value) => $value !== null), $overrides);
    }

    public function forZone(string|AdZone $zone, array $context = [], ?int $limit = null): Collection
    {
        $zone = $zone instanceof AdZone ? $zone : AdZone::query()->where('slug', $zone)->first();

        if (! $zone) {
            return collect();
        }

        $context = $this->normalizeContext($context);

        if (! $this->zoneMatchesContext($zone, $context)) {
            return collect();
        }

        $ads = $zone->ads()
            ->where('is_active', true)
            ->when($zone->supported_ad_types, fn ($query) => $query->whereIn('type', $zone->supported_ad_types))
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get()
            ->filter(fn (Ad $ad) => ! $ad->is_house_ad && $this->adMatchesContext($ad, $context))
            ->values();

        if ($ads->isEmpty() && $zone->fallback_mode === 'house_ads') {
            $ads = $zone->ads()
                ->where('is_active', true)
                ->where('is_house_ad', true)
                ->get()
                ->filter(fn (Ad $ad) => $this->adMatchesContext($ad, $context))
                ->values();
        }

        $limit ??= max($zone->max_ads ?? 1, 1);

        return $this->selectAds($ads, $zone->rotation_mode ?: 'random', $limit);
    }

    public function oneForZone(string|AdZone $zone, array $context = []): ?Ad
    {
        return $this->forZone($zone, $context, 1)->first();
    }

    public function placementForZone(string|AdZone $zone, array $context = []): array
    {
        $zone = $zone instanceof AdZone ? $zone : AdZone::query()->where('slug', $zone)->first();

        if (! $zone) {
            return ['zone' => null, 'ads' => collect(), 'position' => null];
        }

        return [
            'zone' => $zone,
            'ads' => $this->forZone($zone, $context),
            'position' => $zone->isListPlacement() ? $zone->display_after_nth_product : null,
        ];
    }

    protected function normalizeContext(array $context): array
    {
        $request = $context['request'] ?? null;

        if ($request instanceof Request) {
            $context = $this->contextFromRequest($request, $context);
        }

        return $context;
    }

    protected function zoneMatchesContext(AdZone $zone, array $context): bool
    {
        $deviceScope = $zone->device_scope ?: 'all';

        if ($deviceScope === 'all') {
            return true;
        }

        return ($context['device_type'] ?? 'desktop') === $deviceScope;
    }

    protected function adMatchesContext(Ad $ad, array $context): bool
    {
        if (! $ad->isEligibleAt()) {
            return false;
        }

        if ($ad->target_countries && ! in_array($context['country_code'] ?? null, $ad->target_countries, true)) {
            return false;
        }

        if ($ad->target_routes) {
            $routeMatches = in_array($context['route_name'] ?? null, $ad->target_routes, true)
                || in_array($context['page_type'] ?? null, $ad->target_routes, true);

            if (! $routeMatches) {
                return false;
            }
        }

        if ($ad->target_category_ids && ! in_array((int) ($context['category_id'] ?? 0), $ad->target_category_ids, true)) {
            return false;
        }

        $audienceScope = $ad->audience_scope ?: 'all';

        if ($audienceScope !== 'all' && ($context['audience_scope'] ?? 'guest') !== $audienceScope) {
            return false;
        }

        if ($ad->device_types && ! in_array($context['device_type'] ?? 'desktop', $ad->device_types, true)) {
            return false;
        }

        return true;
    }

    protected function selectAds(Collection $ads, string $rotationMode, int $limit): Collection
    {
        if ($ads->isEmpty()) {
            return $ads;
        }

        return match ($rotationMode) {
            'priority' => $ads->sort(function (Ad $left, Ad $right) {
                return [$right->priority, $right->weight, $right->id] <=> [$left->priority, $left->weight, $left->id];
            })->take($limit)->values(),
            'weighted' => $this->weightedSample($ads, $limit),
            default => $ads->shuffle()->take($limit)->values(),
        };
    }

    protected function weightedSample(Collection $ads, int $limit): Collection
    {
        $selected = collect();
        $pool = $ads->values();

        while ($selected->count() < $limit && $pool->isNotEmpty()) {
            $totalWeight = max($pool->sum(fn (Ad $ad) => max($ad->weight + $ad->priority, 1)), 1);
            $target = random_int(1, $totalWeight);
            $running = 0;

            foreach ($pool as $index => $ad) {
                $running += max($ad->weight + $ad->priority, 1);

                if ($running < $target) {
                    continue;
                }

                $selected->push($ad);
                $pool->forget($index);
                $pool = $pool->values();
                break;
            }
        }

        return $selected->values();
    }

    protected function detectDeviceType(?Request $request): string
    {
        $agent = strtolower((string) $request?->userAgent());

        if ($agent !== '' && (str_contains($agent, 'ipad') || str_contains($agent, 'tablet'))) {
            return 'tablet';
        }

        if ($agent !== '' && (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone'))) {
            return 'mobile';
        }

        return 'desktop';
    }
}
