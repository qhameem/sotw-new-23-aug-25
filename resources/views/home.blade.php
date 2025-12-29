@extends('layouts.app')

@section('title', $meta_title ?? 'Software on the Web')

@section('canonical')
    @if (Route::currentRouteName() == 'home')
        <link rel="canonical" href="{{ url('/') }}" />
    @elseif (Route::currentRouteName() == 'products.byDate')
        <link rel="canonical" href="{{ url()->current() }}" />
    @endif

    @if (isset($regularProducts) && $regularProducts instanceof \Illuminate\Contracts\Pagination\Paginator)
        @if ($regularProducts->previousPageUrl())
            <link rel="prev" href="{{ $regularProducts->previousPageUrl() }}">
        @endif
        @if ($regularProducts->nextPageUrl())
            <link rel="next" href="{{ $regularProducts->nextPageUrl() }}">
        @endif
    @endif
@endsection


@section('content')
    @if(!isset($isCategoryPage) || !$isCategoryPage)
        <div class="bg-white px-4 py-2">
            <div class="flex justify-between items-center text-xs" x-data='weeklyNavigation(@json($activeWeeks ?? []))'>
                <button @click="scroll('left')" class="px-2 cursor-pointer text-gray-600 hover:text-gray-800"><</button>
                <div class="flex space-x-4 overflow-x-auto scrollbar-hide" x-ref="container">
                    <template x-for="week in weeks" :key="week.week">
                        <a :href="week.url"
                           :id="'week-' + week.year + '-' + week.week"
                           :class="{
                               'bg-gray-200 text-gray-700 font-bold': week.isSelected,
                               'text-primary-500 font-bold': week.isCurrent && !week.isSelected,
                               'text-gray-400 cursor-not-allowed': !week.isActive,
                               'hover:bg-gray-100': !week.isSelected && !week.isCurrent
                           }"
                           class="px-2 py-1 rounded whitespace-nowrap"
                           @click.prevent="if(week.isActive) window.location.href = week.url">
                            <span x-text="week.label"></span>
                        </a>
                    </template>
                </div>
                <button @click="scroll('right')" class="px-2 cursor-pointer text-gray-600 hover:text-gray-800">></button>
            </div>
        </div>
    @endif

    @if(isset($weekOfYear) && isset($year))
        <x-week-header :week="$weekOfYear" :year="$year" :start-date="$startOfWeek" :end-date="$endOfWeek" />
    @endif

    @if(isset($isCategoryPage) && $isCategoryPage && isset($category) && $category->description)
        <div class="bg-white px-4 py-3">
            <p class="text-sm text-gray-700">{{ $category->description }}</p>
        </div>
    @endif

    <div class="bg-white md:space-y-1">

        @include('partials.products_list', [
            'regularProducts' => $regularProducts ?? collect(),
            'promotedProducts' => $promotedProducts ?? collect(),
            'belowProductListingAd' => $belowProductListingAd ?? null,
            'belowProductListingAdPosition' => $belowProductListingAdPosition ?? null
        ])
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
    function weeklyNavigation(activeWeeks) {
        return {
            weeks: [],
            activeWeeks: activeWeeks,
            init() {
                const now = new Date();
                const currentYear = now.getUTCFullYear();
                const startOfYear = new Date(Date.UTC(currentYear, 0, 1));
                const urlWeek = this.getWeekFromUrl();

                // Loop through each week of the year
                for (let i = 1; i <= 52; i++) {
                    this.weeks.push({
                        year: currentYear,
                        week: i,
                        url: `/week/${currentYear}/${i}`,
                        label: `Week ${i}`,
                        isCurrent: this.getWeekNumber(now) === i,
                        isActive: this.activeWeeks.includes(`${currentYear}-${i}`),
                        isSelected: urlWeek && urlWeek.year == currentYear && urlWeek.week == i,
                    });
                }

                this.$nextTick(() => {
                    // Check if we have specific week/year from backend (for homepage when not in week URL)
                    const backendWeek = @json(isset($weekOfYear) ? $weekOfYear : null);
                    const backendYear = @json(isset($year) ? $year : null);
                    
                    let targetWeek;
                    if (urlWeek) {
                        // If there's a URL week (e.g., /week/2023/15), use that
                        targetWeek = urlWeek;
                    } else if (backendWeek && backendYear) {
                        // If we're on homepage and backend provided week info, use that
                        targetWeek = { year: backendYear, week: backendWeek };
                    } else {
                        // Otherwise, default to current week
                        targetWeek = { year: currentYear, week: this.getWeekNumber(now) };
                    }
                    
                    const targetElement = this.$refs.container.querySelector(`#week-${targetWeek.year}-${targetWeek.week}`);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
                    }
                });
            },
            getWeekFromUrl() {
                const path = window.location.pathname;
                const match = path.match(/^\/week\/(\d{4})\/(\d{1,2})$/);
                return match ? { year: parseInt(match[1]), week: parseInt(match[2]) } : null;
            },
            getWeekNumber(d) {
                // Use ISO 8601 standard for week calculation
                var date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
                // Thursday in current week decides the year.
                date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay()||7));
                // January 4 is always in week 1.
                var yearStart = new Date(Date.UTC(date.getUTCFullYear(),0,4));
                // Adjust to Monday in week 1 considering if the day was January 4 was a Sunday
                yearStart.setUTCDate(yearStart.getUTCDate() - (yearStart.getUTCDay()||7) + 1);
                var weekNo = Math.ceil((((date - yearStart) / 86400000) + 1)/7);
                return weekNo;
            },
            scroll(direction) {
                const container = this.$refs.container;
                const weekElement = container.querySelector('a');
                if (weekElement) {
                    const scrollAmount = (weekElement.offsetWidth + 16) * 7;
                    if (direction === 'left') {
                        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
                    } else {
                        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                    }
                }
            }
        }
    }
</script>
@endpush
