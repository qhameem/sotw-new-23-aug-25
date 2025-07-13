@extends('layouts.app')

@section('title', $pageTitle ?? 'Software on the Web')

@section('actions')
    <div class="md:flex items-center space-x-2">
        @if(!isset($isCategoryPage) || !$isCategoryPage)
        <a href="{{ route('categories.index') }}" class="bg-white border border-gray-300 hover:bg-gray-100 text-sm font-semibold py-1 px-3 rounded-lg">
            Categories
        </a>
        @endif
       <x-add-product-button />
    </div>
@endsection

@section('content')
    <div>
        @if(isset($isCategoryPage) && $isCategoryPage && isset($category) && $category->description)
            <div class="bg-white px-4 py-3">
                <p class="text-sm text-gray-700">{{ $category->description }}</p>
            </div>
        @elseif(!isset($isCategoryPage) || !$isCategoryPage)
            <div class="bg-white px-4 py-2">
                <div class="flex justify-between items-center text-xs" x-data='dailyNavigation(@json($activeDates ?? []))'>
                    <button @click="scroll('left')" class="px-2 cursor-pointer text-gray-600 hover:text-gray-800">&lt;</button>
                    <div class="flex space-x-4 overflow-x-auto scrollbar-hide" x-ref="container">
                        <template x-for="day in days" :key="day.date">
                            <a :href="day.url"
                               :id="'day-' + day.date"
                               :class="{ 
                                   'bg-gray-200 text-gray-700 font-bold': day.isSelected,
                                   'text-primary-500 font-bold': day.isToday && !day.isSelected,
                                   'text-gray-400 cursor-not-allowed': !day.isActive && !day.isFuture,
                                   'hover:bg-gray-100': !day.isSelected && !day.isToday && !day.isFuture 
                               }"
                               class="px-2 py-1 rounded whitespace-nowrap"
                               @click.prevent="if(day.isActive || day.isToday) window.location.href = day.url">
                                <span x-text="day.label"></span>
                            </a>
                        </template>
                    </div>
{{-- In-page headings and breadcrumbs --}}
        @if(isset($isCategoryPage) && $isCategoryPage)
            <div>
                <h1 class="text-lg md:text-base pt-4 font-semibold tracking-tight">{{ $title }}</h1>

                @if(isset($category))
                    <nav class="-mt-2 mb-4" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 text-xs text-gray-500">
                            <li>
                                <a href="{{ route('categories.index') }}" class="text-gray-900 hover:text-primary-600">Category</a>
                            </li>
                            <li>
                                <span>/</span>
                            </li>
                            <li>
                                <span>{{ $category->name }}</span>
                            </li>
                        </ol>
                    </nav>
                @endif
            </div>
        @elseif(!isset($isCategoryPage) && !isset($displayDateString)) {{-- Only show on main home page --}}
            <h2 class="text-base font-semibold hidden md:block">Top Software Products</h2>
            <h2 class="text-base font-semibold md:hidden">Top Products</h2>
        @endif
                    <button @click="scroll('right')" class="px-2 cursor-pointer text-gray-600 hover:text-gray-800">&gt;</button>
                </div>
            </div>
        @endif
    </div>

    <div class="bg-gradient-to-t from-white to-gray-50 md:space-y-1">
        @if(isset($displayDateString) && (!isset($isCategoryPage) || !$isCategoryPage))
            <div class="py-2 pl-4 pr-4">
                <h1 class="hidden md:block text-lg md:text-base pt-4 font-semibold tracking-tight">{{ $title }}</h1>
                <h1 class="md:hidden text-lg md:text-base pt-4 font-semibold tracking-tight">{{ $mobileTitle }}</h1>
            </div>
            <div class="flex justify-between items-center py-2 pl-4 pr-4">
                <div>
                    @php
                        $displayDate = \Carbon\Carbon::parse($displayDateString);
                        $dayOfYear = $displayDate->dayOfYear;
                    @endphp
                    <h2 class="text-2xl font-noto-serif font-light tracking-tighter">Day {{ $dayOfYear }}</h2>
                    <p class="text-xs text-gray-500">{{ $displayDate->format('j F') }}</p>
                </div>
            </div>
        @endif

        @include('partials.products_list_with_pagination', [
            'promotedProducts' => $promotedProducts,
            'regularProducts' => $regularProducts,
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
    function dailyNavigation(activeDates) {
        return {
            days: [],
            activeDates: activeDates,
            init() {
                const now = new Date();
                const today = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate()));
                const startOfYear = new Date(Date.UTC(today.getUTCFullYear(), 0, 1));
                const urlDate = this.getDateFromUrl();

                for (let d = new Date(startOfYear); d <= today; d.setUTCDate(d.getUTCDate() + 1)) {
                    const year = d.getUTCFullYear();
                    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
                    const day = String(d.getUTCDate()).padStart(2, '0');
                    const dateString = `${year}-${month}-${day}`;

                    const startOfYearForDayCalc = new Date(Date.UTC(year, 0, 0));
                    const dayOfYear = Math.ceil((d - startOfYearForDayCalc) / 86400000);

                    this.days.push({
                        date: dateString,
                        url: `/date/${dateString}`,
                        label: `Day ${dayOfYear}`,
                        isToday: d.getTime() === today.getTime(),
                        isFuture: d > today,
                        isActive: this.activeDates.includes(dateString),
                        isSelected: urlDate === dateString
                    });
                }

                this.$nextTick(() => {
                    const targetDate = urlDate || today.toISOString().split('T')[0];
                    const targetElement = this.$refs.container.querySelector(`#day-${targetDate}`);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
                    } else {
                        const lastActiveDay = this.days.slice().reverse().find(d => d.isActive);
                        if(lastActiveDay) {
                            const lastActiveElement = this.$refs.container.querySelector(`#day-${lastActiveDay.date}`);
                            if(lastActiveElement) {
                                lastActiveElement.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
                            }
                        }
                    }
                });
            },
            getDateFromUrl() {
                const path = window.location.pathname;
                const match = path.match(/^\/date\/(\d{4}-\d{2}-\d{2})$/);
                return match ? match[1] : null;
            },
            scroll(direction) {
                const container = this.$refs.container;
                const dayElement = container.querySelector('a');
                if (dayElement) {
                    const scrollAmount = (dayElement.offsetWidth + 16) * 7;
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
