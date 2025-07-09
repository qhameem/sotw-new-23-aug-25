@php
use Illuminate\Support\Facades\Storage;
@endphp
@extends('layouts.app')

@section('title', 'Premium Products')

@section('content')
@section('title')
    <h1 class="text-xl font-bold text-gray-800">Premium Products</h1>
@endsection

@section('actions')
@endsection

@section('content')
    <div class="p-4">
        <div class="space-y-4">
            @forelse($premiumProducts as $premiumProduct)
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex items-center">
                        <img src="{{ $premiumProduct->product->logo ? (Str::startsWith($premiumProduct->product->logo, 'http') ? $premiumProduct->product->logo : Storage::url($premiumProduct->product->logo)) : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($premiumProduct->product->link) }}" alt="{{ $premiumProduct->product->name }} logo" class="w-10 h-10 mr-4 rounded-md object-cover">
                        <div>
                            <h3 class="font-semibold">{{ $premiumProduct->product->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $premiumProduct->product->tagline }}</p>
                            <p class="text-xs text-gray-400">Expires on: {{ $premiumProduct->expires_at->format('d F Y') }} (in {{ $premiumProduct->expires_at->diffForHumans(null, true) }})</p>
                        </div>
                    </div>
                    <form action="{{ route('admin.premium-products.destroy', $premiumProduct->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this product from premium spots?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-gray-500">No premium products found.</p>
            @endforelse
        </div>
    </div>
@endsection
@endsection