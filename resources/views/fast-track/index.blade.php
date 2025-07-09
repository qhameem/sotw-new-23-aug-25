@extends('layouts.app')

@section('title')
 <h1 class="text-lg md:text-base pt-1.5 font-semibold tracking-tight">{{ $title }}</h1>
@endsection

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ url()->previous() ?? route('home') }}" class="text-gray-700 text-xs hover:underline">
            &larr; Back to previous page
        </a>
    </div>

    <h1 class="text-3xl font-bold mb-4">Fast-Track Your Products</h1>

    

    <form action="{{ route('stripe.checkout') }}" method="POST" class="p-4">
        @csrf
        <input type="hidden" name="product_ids" id="product_ids">
        <div id="publish-dates"></div>
        <button type="submit" id="payment-button" class="bg-primary-500 text-white px-4 py-2 rounded w-full" disabled>Select at least one product from below to launch</button>
    </form>

    <div class="mt-4 p-4">
        <h2 class="text-xl font-semibold mb-4">Select the products you want to fast-track</h2>
        <div id="cart" class="mb-4 p-4 border rounded bg-gray-50">
            <p class="font-bold">Total Price: $<span id="total-price">0</span></p>
        </div>
        @foreach($products as $product)
            @include('partials.product_card_for_fast_track', ['product' => $product, 'loop' => $loop, 'alpineProducts' => $alpineProducts, 'isFirst' => $loop->first])
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        const totalPriceElement = document.getElementById('total-price');
        const productIdsElement = document.getElementById('product_ids');
        const paymentButton = document.getElementById('payment-button');
        const publishDatesContainer = document.getElementById('publish-dates');

        function updateCartAndButton() {
            let totalPrice = 0;
            const selectedProductIds = [];
            publishDatesContainer.innerHTML = '';

            checkboxes.forEach(cb => {
                const productCard = cb.closest('article');
                const datePicker = productCard.querySelector('.product-date');

                if (cb.checked) {
                    totalPrice += parseFloat(cb.dataset.price);
                    selectedProductIds.push(cb.value);
                    datePicker.disabled = false;

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `publish_dates[${cb.value}]`;
                    hiddenInput.value = datePicker.value;
                    publishDatesContainer.appendChild(hiddenInput);
                } else {
                    datePicker.disabled = true;
                }
            });

            totalPriceElement.textContent = totalPrice;
            productIdsElement.value = selectedProductIds.join(',');

            const hasSelection = selectedProductIds.length > 0;
            paymentButton.disabled = !hasSelection;
            paymentButton.classList.toggle('opacity-50', !hasSelection);
            paymentButton.classList.toggle('cursor-not-allowed', !hasSelection);

            if (hasSelection) {
                paymentButton.textContent = `Pay & launch for $${totalPrice}`;
            } else {
                paymentButton.textContent = 'Select at least one product from below to launch';
            }
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateCartAndButton);
            const productCard = checkbox.closest('article');
            const datePicker = productCard.querySelector('.product-date');
            datePicker.addEventListener('change', updateCartAndButton);
        });

        // Initial state setup
        updateCartAndButton();
    });
</script>
@endsection