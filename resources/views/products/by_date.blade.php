@extends('layouts.app')

@section('content')
<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-20">
    <div class="flex flex-col gap-2 items-left pl-4 pt-9 mb-2 md:mb-6 md:pt-4">
        <div class="flex items-center space-x-4">
            <h1 class="text-xl md:text-2xl font-bold tracking-tight">Top Software Products on</h1>
            <input type="date" id="date-picker" value="{{ $date }}" class="ml-4 border-gray-300 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; line-height: 1rem;">
        </div>
    </div>

    <div class="md:space-y-1">
        @include('partials.products_list_with_pagination', [
            'promotedProducts' => $promotedProducts,
            'regularProducts' => $regularProducts,
            'belowProductListingAd' => $belowProductListingAd ?? null,
            'belowProductListingAdPosition' => $belowProductListingAdPosition ?? null
        ])
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const datePicker = document.getElementById('date-picker');
    if (datePicker) {
        datePicker.addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                window.location.href = `/date/${selectedDate}`;
            }
        });
    }
});
</script>
@endpush