@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-4xl', 'headerPadding' => 'px-4 md:pt-4'])

@if (!request()->routeIs('home'))
    @section('title', $meta_title ?? $pageTitle ?? 'Software on the Web')
@endif

@section('meta_description', request()->routeIs('home') ? 'Find new SaaS tools every week. Browse curated picks across AI, productivity, developer tools, and design software on Software on the Web.' : ($metaDescription ?? $meta_description ?? ''))

@section('header-title')
    {{ $title ?? '' }}
@endsection

@section('before_header_title')
    @guest
        @if(request()->routeIs('home', 'products.byWeek', 'products.byMonth', 'products.byYear'))
            <x-site-intro-card class="hidden md:block" />
        @endif
    @endguest
@endsection

@section('canonical')
    @php
        $paginatedCanonicalUrl = null;

        if (isset($regularProducts) && $regularProducts instanceof \Illuminate\Pagination\LengthAwarePaginator && $regularProducts->currentPage() > 1) {
            $paginatedCanonicalUrl = $regularProducts->url($regularProducts->currentPage());
        }
    @endphp

    @if (Route::currentRouteName() == 'home')
        <link rel="canonical" href="{{ url('/') }}" />
    @elseif (Route::currentRouteName() == 'products.byWeek')
        <link rel="canonical" href="{{ route('home') }}" />
    @elseif (Route::currentRouteName() == 'products.byDate')
        <link rel="canonical" href="{{ url()->current() }}" />
    @elseif (Route::currentRouteName() == 'products.byMonth')
        <link rel="canonical" href="{{ $paginatedCanonicalUrl ?: route('products.byMonth', ['year' => request()->route('year'), 'month' => request()->route('month')]) }}" />
    @elseif (Route::currentRouteName() == 'products.byYear')
        <link rel="canonical" href="{{ $paginatedCanonicalUrl ?: route('products.byYear', ['year' => request()->route('year')]) }}" />
    @elseif (in_array(Route::currentRouteName(), ['categories.show', 'categories.show.page'], true))
        <link rel="canonical" href="{{ $categoryCanonicalUrl ?? url()->current() }}" />
    @endif

    @if (!empty($categoryPagination))
        @if (!empty($categoryPagination['previous_url']))
            <link rel="prev" href="{{ $categoryPagination['previous_url'] }}">
        @endif
        @if (!empty($categoryPagination['next_url']))
            <link rel="next" href="{{ $categoryPagination['next_url'] }}">
        @endif
    @elseif (isset($regularProducts) && $regularProducts instanceof \Illuminate\Contracts\Pagination\Paginator)
        @if ($regularProducts->previousPageUrl())
            <link rel="prev" href="{{ $regularProducts->previousPageUrl() }}">
        @endif
        @if ($regularProducts->nextPageUrl())
            <link rel="next" href="{{ $regularProducts->nextPageUrl() }}">
        @endif
    @endif
@endsection

@section('preloads')
    @if(!isset($isFuture) || !$isFuture)
        @include('partials.product_logo_preloads', [
            'products' => \App\Support\ProductLogo::productListItems(
                $regularProducts ?? collect(),
                $promotedProducts ?? collect()
            ),
        ])
    @endif
@endsection


@if((isset($isFuture) && $isFuture) || !empty($shouldNoindexArchive))
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <div class="flex-shrink-0 z-10 relative" style="background-color: var(--color-body-bg, #ffffff);">
    @guest
        @if(request()->routeIs('home', 'products.byWeek', 'products.byMonth', 'products.byYear'))
            <x-site-intro-card class="md:hidden" />
        @endif
    @endguest

    @if(!isset($isCategoryPage) || !$isCategoryPage)
        <div class="px-4 py-2">
            <div class="flex justify-between items-center text-xs" x-data="weekNavigationScroller()" x-init="init()">
                <button @click="scroll('left')" class="px-2 text-sm cursor-pointer text-gray-600 hover:text-rose-500">
                    &larr;
                </button>
                <div class="flex-1 flex overflow-x-auto scrollbar-hide mx-4" x-ref="container">
                    @foreach(($weekNavigationItems ?? []) as $weekItem)
                        <a href="{{ $weekItem['url'] }}" wire:navigate.hover
                           id="week-{{ $weekItem['year'] }}-{{ $weekItem['week'] }}"
                           @class([
                               'flex-shrink-0 w-[25%] md:w-[14.2857%] py-1 rounded text-center',
                               'bg-gray-200 text-gray-700 font-bold' => $weekItem['isSelected'],
                               'text-primary-500 font-bold' => $weekItem['isCurrent'] && !$weekItem['isSelected'],
                               'hover:bg-gray-100' => !$weekItem['isSelected'] && !$weekItem['isCurrent'],
                           ])>
                            <span>{{ $weekItem['label'] }}</span>
                        </a>
                    @endforeach
                </div>
                <button @click="scroll('right')" class="px-2 cursor-pointer text-sm text-gray-600 hover:text-rose-500">
                    &rarr;
                </button>
            </div>
        </div>
        <div class="shadow-sm border-t border-gray-100"></div>
    @endif

    @if(isset($weekOfYear) && isset($year))
        <x-week-header :week="$weekOfYear" :year="$year" :start-date="$startOfWeek" :end-date="$endOfWeek" />
    @endif

    @if(isset($isCategoryPage) && $isCategoryPage && isset($category) && $category->description)
        <div class="px-4 pb-4 pt-4 md:pt-2 lg:pt-0">
            <p class="text-sm text-gray-800">{{ $category->description }}</p>
        </div>
    @endif
    </div>

    <!-- Product List Container -->
    <div class="flex-1 min-h-[400px] flex flex-col" style="background-color: var(--color-body-bg, #ffffff);">
        @if(isset($isFuture) && $isFuture)
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                <div class="w-24 h-24 mb-6 text-gray-200">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">The week is coming soon...</h2>
                <p class="text-gray-500 max-w-xs">We're gathering the best software for this upcoming week. Check back later!</p>
            </div>
        @else
            <div class="md:space-y-1">
                @include('partials.products_list', [
                    'regularProducts' => $regularProducts ?? collect(),
                    'promotedProducts' => $promotedProducts ?? collect(),
                    'belowProductListingAd' => $belowProductListingAd ?? null,
                    'belowProductListingAdPosition' => $belowProductListingAdPosition ?? null
                ])
            </div>
        @endif
    </div>
@endsection

@push('styles')
<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
@endpush

@push('scripts')
<script>
    function weekNavigationScroller() {
        return {
            init() {
                this.$nextTick(() => {
                    const targetElement = this.$refs.container.querySelector('.bg-gray-200');
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
                    }
                });
            },
            scroll(direction) {
                const container = this.$refs.container;
                const scrollAmount = container.clientWidth; // Scroll exactly one view width (7 items)
                if (direction === 'left') {
                    container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
                } else {
                    container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                }
            }
        }
    }
</script>
@endpush
