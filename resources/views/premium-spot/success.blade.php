@extends('layouts.app')

@section('title')
    <h1 class="text-lg md:text-base pt-1.5 font-semibold tracking-tight">Premium Spot Purchase Successful</h1>
@endsection

@section('content')
<div class="container mx-auto p-4">
    <div class="flex justify-between items-center mb-4">
        <a href="{{ route('home') }}" class="text-gray-700 text-xs hover:underline">
            &larr; Back to Home
        </a>
    </div>

    <h1 class="text-3xl font-bold mb-4">Congratulations!</h1>

    <div class="p-4 bg-green-100 text-green-800 rounded">
        <p>Your payment was successful. The following products now have a premium spot:</p>
    </div>

    <div class="mt-4 p-4">
        @foreach($products as $product)
            <div class="flex items-center justify-between p-4 border rounded-lg mb-4">
                <div class="flex items-center">
                    <img src="{{ $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : Storage::url($product->logo)) : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} logo" class="w-10 h-10 mr-4 rounded-md object-cover">
                    <div>
                        <h3 class="font-semibold">{{ $product->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $product->tagline }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection