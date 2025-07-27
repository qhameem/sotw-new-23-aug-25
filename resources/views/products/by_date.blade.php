@extends('layouts.app')

@section('content')
<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-20">
    <div class="flex flex-col gap-2 items-left pl-4 pt-9 mb-2 md:mb-6 md:pt-4">
        <div class="flex items-center space-x-4">
            <h1 class="text-xl md:text-2xl font-bold tracking-tight">Top Products</h1>
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