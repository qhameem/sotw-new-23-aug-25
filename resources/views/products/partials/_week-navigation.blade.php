@php
    use App\Support\ProductLogo;

    $previousProduct = $weekNavigation['previous'] ?? null;
    $nextProduct = $weekNavigation['next'] ?? null;
@endphp

@if($previousProduct || $nextProduct)
    <nav aria-label="Products from week {{ $weekNavigation['week'] }}" class="mt-10 border-t border-gray-100 pt-8 print:hidden">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">More from this week</p>
                <p class="mt-1 text-sm text-gray-500">Product {{ $weekNavigation['position'] }} of {{ $weekNavigation['total'] }}</p>
            </div>
            <a href="{{ $weekNavigation['url'] }}"
                class="shrink-0 text-sm font-semibold text-gray-600 transition hover:text-primary-700 hover:underline"
                aria-label="View all {{ $weekNavigation['total'] }} products from week {{ $weekNavigation['week'] }}">
                View week {{ $weekNavigation['week'] }}
            </a>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @if($previousProduct)
                @php($previousLogo = ProductLogo::storedUrl($previousProduct))
                <a href="{{ route('products.show', ['product' => $previousProduct->slug]) }}"
                    wire:navigate.hover rel="prev"
                    aria-label="Previous product: {{ $previousProduct->name }}"
                    class="group flex min-w-0 items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 transition hover:border-gray-300 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-100 bg-gray-50 text-sm font-bold text-gray-600">
                        @if($previousLogo)
                            <img src="{{ $previousLogo }}" alt="" width="44" height="44" loading="lazy" decoding="async" class="h-full w-full object-contain">
                        @else
                            {{ ProductLogo::initial($previousProduct) }}
                        @endif
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs text-gray-400">← Previous product</span>
                        <span class="mt-0.5 block truncate text-sm font-semibold text-gray-800 group-hover:text-primary-700">{{ $previousProduct->name }}</span>
                    </span>
                </a>
            @else
                <span class="hidden sm:block"></span>
            @endif

            @if($nextProduct)
                @php($nextLogo = ProductLogo::storedUrl($nextProduct))
                <a href="{{ route('products.show', ['product' => $nextProduct->slug]) }}"
                    wire:navigate.hover rel="next"
                    aria-label="Next product: {{ $nextProduct->name }}"
                    class="group flex min-w-0 items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white p-3 text-right transition hover:border-gray-300 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 sm:col-start-2">
                    <span class="min-w-0">
                        <span class="block text-xs text-gray-400">Next product →</span>
                        <span class="mt-0.5 block truncate text-sm font-semibold text-gray-800 group-hover:text-primary-700">{{ $nextProduct->name }}</span>
                    </span>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-100 bg-gray-50 text-sm font-bold text-gray-600">
                        @if($nextLogo)
                            <img src="{{ $nextLogo }}" alt="" width="44" height="44" loading="lazy" decoding="async" class="h-full w-full object-contain">
                        @else
                            {{ ProductLogo::initial($nextProduct) }}
                        @endif
                    </span>
                </a>
            @endif
        </div>
    </nav>
@endif
