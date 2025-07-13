@extends('layouts.app')

@section('title', 'Get a Premium Spot')

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ url()->previous() ?? route('home') }}" class="text-gray-700 text-xs hover:underline">
            &larr; Back to previous page
        </a>
    </div>

    <h1 class="text-3xl font-bold mb-4">Get a Premium Spot</h1>

    @if($availableSpots > 0)
        <form action="{{ route('premium-spot.checkout') }}" method="POST" class="p-4">
            @csrf
            <input type="hidden" name="product_ids" id="product_ids">
            <button type="submit" id="payment-button" class="bg-primary-500 text-white px-4 py-2 rounded w-full" disabled>Select up to {{ $availableSpots }} product(s) to get a premium spot</button>
        </form>

        <div class="mt-4 p-4">
            <h2 class="text-xl font-semibold mb-4">Select the products you want to feature</h2>
            <div id="cart" class="mb-4 p-4 border rounded bg-gray-50" data-available-spots="{{ (int) $availableSpots }}">
                <p class="font-bold">Total Price: $<span id="total-price">0</span></p>
            </div>
            @foreach($products as $product)
                @include('partials.product_card_for_premium_spot', ['product' => $product, 'loop' => $loop, 'alpineProducts' => $alpineProducts, 'isFirst' => $loop->first])
            @endforeach
        </div>
    @else
        <div class="p-4 text-center">
            <p class="text-lg text-gray-700">There are currently no premium spots available. Please check back later.</p>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const availableSpots = parseInt(document.getElementById('cart').dataset.availableSpots);
        const checkboxes = document.querySelectorAll('.product-checkbox');
        const totalPriceElement = document.getElementById('total-price');
        const productIdsElement = document.getElementById('product_ids');
        const paymentButton = document.getElementById('payment-button');

        function updateCartAndButton() {
            let totalPrice = 0;
            const selectedProductIds = [];

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    totalPrice += parseFloat(cb.dataset.price);
                    selectedProductIds.push(cb.value);
                }
            });

            totalPriceElement.textContent = totalPrice;
            productIdsElement.value = selectedProductIds.join(',');

            const hasSelection = selectedProductIds.length > 0;
            const canSelectMore = selectedProductIds.length <= availableSpots;

            paymentButton.disabled = !hasSelection || !canSelectMore;
            paymentButton.classList.toggle('opacity-50', !hasSelection || !canSelectMore);
            paymentButton.classList.toggle('cursor-not-allowed', !hasSelection || !canSelectMore);

            if (hasSelection) {
                if (canSelectMore) {
                    paymentButton.textContent = `Pay $${totalPrice} for ${selectedProductIds.length} premium spot(s)`;
                } else {
                    paymentButton.textContent = `You can only select up to ${availableSpots} product(s)`;
                }
            } else {
                paymentButton.textContent = `Select up to ${availableSpots} product(s) to get a premium spot`;
            }
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateCartAndButton);
        });

        // Initial state setup
        updateCartAndButton();
    });
</script>
@endsection