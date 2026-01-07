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
    <div class="flex-shrink-0 bg-white z-10 relative">
    @if(!isset($isCategoryPage) || !$isCategoryPage)
        <div class="bg-white px-4 py-2">
            <div class="flex justify-between items-center text-xs" x-data='weeklyNavigation(@json($activeWeeks ?? []))'>
                <button @click="scroll('left')" class="px-2 cursor-pointer text-gray-600 hover:text-gray-800"><</button>
                <div class="flex-1 flex overflow-x-auto scrollbar-hide mx-4" x-ref="container">
                    <template x-for="week in weeks" :key="week.year + '-' + week.week">
                        <a :href="week.url"
                           :id="'week-' + week.year + '-' + week.week"
                           :class="{
                               'bg-gray-200 text-gray-700 font-bold': week.isSelected,
                               'text-primary-500 font-bold': week.isCurrent && !week.isSelected,
                               'text-gray-400 cursor-not-allowed': !week.isActive,
                               'hover:bg-gray-100': !week.isSelected && !week.isCurrent
                           }"
                           class="flex-shrink-0 w-[14.2857%] py-1 rounded text-center"
                           @click.prevent="if(week.isActive) window.location.href = week.url">
                            <span x-text="week.label"></span>
                        </a>
                    </template>
                </div>
                <button @click="scroll('right')" class="px-2 cursor-pointer text-gray-600 hover:text-gray-800">></button>
            </div>
        </div>
        <div class="shadow-sm border-t border-gray-100"></div>
    @endif

    @if(isset($weekOfYear) && isset($year))
        <x-week-header :week="$weekOfYear" :year="$year" :start-date="$startOfWeek" :end-date="$endOfWeek" />
    @endif

    @if(isset($isCategoryPage) && $isCategoryPage && isset($category) && $category->description)
        <div class="bg-white px-4 py-3">
            <p class="text-sm text-gray-700">{{ $category->description }}</p>
        </div>
    @endif
    </div>

    <!-- Product List Container -->
    <div class="bg-white flex-1">
        <div class="md:space-y-1">
            @include('partials.products_list', [
                'regularProducts' => $regularProducts ?? collect(),
                'promotedProducts' => $promotedProducts ?? collect(),
                'belowProductListingAd' => $belowProductListingAd ?? null,
                'belowProductListingAdPosition' => $belowProductListingAdPosition ?? null
            ])
        </div>
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
                const urlWeek = this.getWeekFromUrl();
                
                // Get backend data
                const backendWeek = @json(isset($weekOfYear) ? $weekOfYear : null);
                const backendYear = @json(isset($year) ? $year : null);
                
                // Determine which year and week to display/select
                const displayYear = urlWeek ? urlWeek.year : (backendYear || now.getUTCFullYear());
                const selectedWeek = urlWeek ? urlWeek.week : backendWeek;

                // Loop through previous, current, and next year to allow scrolling
                const years = [displayYear - 1, displayYear, displayYear + 1];

                years.forEach(year => {
                    const weeksInYear = this.getWeeksInYear(year);
                    for (let i = 1; i <= weeksInYear; i++) {
                        const isSelected = (year === displayYear && i === selectedWeek);
                        // Backend activeWeeks are in format "YYYY-W" or "YYYY-WW" (padded or unpadded)
                        // Adjust isActive check to handle unpadded week numbers from DB if necessary
                        const weekKey = `${year}-${i}`; 
                        const weekKeyPadded = `${year}-${String(i).padStart(2, '0')}`;
                        
                        this.weeks.push({
                            year: year,
                            week: i,
                            url: `/week/${year}/${i}`,
                            label: `Week ${i}`,
                            isCurrent: this.getWeekNumber(now) === i && now.getUTCFullYear() === year,
                            isActive: this.activeWeeks.includes(weekKey) || this.activeWeeks.includes(weekKeyPadded),
                            isSelected: isSelected,
                        });
                    }
                });

                this.$nextTick(() => {
                    const targetElement = this.$refs.container.querySelector(`#week-${displayYear}-${selectedWeek}`);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
                    }
                });
            },
            getWeeksInYear(year) {
                const d = new Date(year, 11, 31);
                const week = this.getWeekNumber(d);
                return week === 1 ? 52 : week;
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
