@extends('layouts.app')

@section('title', 'Premium Products Confirmation')

@section('content')
<x-main-content-layout>
    <x-slot:title>
        <h1 class="text-xl font-bold text-gray-800">Congratulations!</h1>
    </x-slot:title>

    <div class="p-4">
        <div class="flex items-center p-4 mb-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50" role="alert">
            <div class="px-3"><x-simpleline-check class="w-4 h-4" /></div>
            <div>
                <span class="font-semibold">Your products have been upgraded to premium status!</span>
            </div>
        </div>

        <p class="text-gray-600 mb-4">The following products are now featured with premium status:</p>

        <div class="space-y-4">
            @foreach($products as $product)
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center">
                        <img src="{{ $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} logo" class="w-10 h-10 mr-4 rounded-md object-cover">
                        <div>
                            <h3 class="font-semibold">{{ $product->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $product->tagline }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-main-content-layout>
@endsection