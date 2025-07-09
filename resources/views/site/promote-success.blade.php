@extends('layouts.app')

@section('title')
    <h1 class="text-lg md:text-base pt-4 font-semibold tracking-tight">Payment Successful!</h1>
@endsection

@section('content')
<div class="container mx-auto p-4">
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
        <p class="font-bold">Success!</p>
        <p>Your {{ count($products) > 1 ? 'products have' : 'product has' }} been successfully published and/or scheduled.</p>
    </div>

    <h2 class="text-2xl font-bold mb-4">Your Products</h2>
    <div class="space-y-4">
        @foreach($products as $product)
            @include('partials.product_card_for_success_page', ['product' => $product])
        @endforeach
    </div>
</div>
@endsection