@php
    $adDelivery = app(\App\Services\AdDeliveryService::class);
    $sidebarRouteName = Route::currentRouteName();
    $productPrimaryCategoryId = isset($product) ? $product->categories->first()?->id : null;
    $sidebarCategoryId = $sidebarCategoryId
        ?? ($category->id ?? $productPrimaryCategoryId);

    $sidebarAdContext = $adDelivery->contextFromRequest(request(), [
        'route_name' => $sidebarRouteName,
        'page_type' => $sidebarRouteName,
        'category_id' => $sidebarCategoryId,
    ]);

    $sidebarTopAd = $sidebarTopAd ?? $adDelivery->oneForZone('sidebar-top', $sidebarAdContext);
    $sponsors = $sponsors ?? $adDelivery->forZone(
        'sponsors',
        $sidebarAdContext,
        config('performance.max_sponsors_display', 6)
    );

    if ($sidebarRouteName === 'products.show' && $sidebarCategoryId) {
        $fallbackContext = $adDelivery->contextFromRequest(request(), [
            'route_name' => 'categories.show',
            'page_type' => 'categories.show',
            'category_id' => $sidebarCategoryId,
        ]);

        $sidebarTopAd ??= $adDelivery->oneForZone('sidebar-top', $fallbackContext);

        if ($sponsors->isEmpty()) {
            $sponsors = $adDelivery->forZone(
                'sponsors',
                $fallbackContext,
                config('performance.max_sponsors_display', 6)
            );
        }
    }
@endphp

@if($sidebarTopAd || $sponsors->isNotEmpty())
    <div class="px-4 pb-1">
        <h3 class="text-sm font-semibold text-gray-800">Featured</h3>
    </div>
@endif

@if($sidebarTopAd)
    <div class="p-4 pt-2">
        @include('partials.render_ad_block', ['ad' => $sidebarTopAd, 'zoneSlug' => 'sidebar-top'])
    </div>
@endif

@if($sponsors->isNotEmpty())
    <div class="p-4 rounded-xl border-l-4 bg-stone-100 transition-colors hover:bg-stone-200/70" style="border-left-color: var(--color-primary-500);">
        <ul class="space-y-4">
            @foreach($sponsors as $sponsor)
                <li>
                    <a href="{{ route('ads.click', ['ad' => $sponsor, 'zone' => 'sponsors']) }}" target="_blank" rel="noopener noreferrer" class="group flex items-center space-x-3 rounded-lg p-2" aria-label="Open {{ $sponsor->internal_name }} website">
                        <img src="{{ $sponsor->image_url }}" alt="{{ $sponsor->internal_name }}"
                            class="w-10 h-10 rounded-xl object-cover">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1 font-semibold text-gray-900">
                                <span class="truncate">{{ $sponsor->internal_name }}</span>
                                <span class="ad-link-out-icon inline-flex h-4 w-4 flex-shrink-0 items-center justify-center text-gray-400 transition group-hover:text-gray-700" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8" />
                                    </svg>
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 line-clamp-2">{{ $sponsor->tagline }}</p>
                        </div>
                    </a>
                    <img src="{{ route('ads.impression', ['ad' => $sponsor, 'zone' => 'sponsors']) }}" alt="" class="hidden" width="1" height="1">
                </li>
            @endforeach
        </ul>
    </div>
@endif
