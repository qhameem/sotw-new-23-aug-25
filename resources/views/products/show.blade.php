@extends('layouts.app')

@section('title', $product->name . ' | Software on the Web')
@section('description', $product->product_page_tagline)

@section('content')
<x-main-content-layout>
    <x-slot:title>
        <div class="flex items-center">
            <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-700 mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            @if($product->logo)
                <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="{{ $product->name }} logo" class="w-8 h-8 object-contain rounded-lg mr-3">
            @elseif($product->link)
                <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="w-8 h-8 object-contain rounded-lg mr-3">
            @endif
            <h1 class="text-lg font-semibold tracking-tight">{{ $product->name }}</h1>
        </div>
    </x-slot:title>
    <x-slot:actions></x-slot:actions>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-white rounded-lg max-w-3xl mx-auto p-6 md:p-8">
            {{-- Product Header: Logo, Name, Tagline --}}
            <div class="flex items-center mb-4">
                {{-- Logo --}}
                <div class="mr-4">
                    @if($product->logo)
                        <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="{{ $product->name }} logo" class="w-16 h-16 object-contain rounded-lg">
                    @elseif($product->link)
                        <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="w-16 h-16 object-contain rounded-lg">
                    @else
                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    @endif
                </div>
                {{-- Name and Tagline --}}
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                    <p class="text-gray-600 mt-1">{{ $product->product_page_tagline }}</p>
                    @if($product->categories->whereIn('slug', ['analytics-tool', 'marketing'])->count() > 0)
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($product->categories->whereIn('slug', ['analytics-tool', 'marketing']) as $category)
                                <span class="text-sm text-gray-500">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between mb-6">
                @if($product->user && !$product->user->hasRole('admin'))
                    <div class="flex items-center">
                        <img src="{{ $product->user->profile_photo_url }}" alt="{{ $product->user->name }}" class="w-8 h-8 rounded-full mr-2">
                        <span class="text-sm font-semibold text-gray-800">{{ $product->user->name }}</span>
                    </div>
                @endif
                <div class="flex space-x-2">
                    <a href="{{ $product->link . (strpos($product->link, '?') === false ? '?' : '&') }}utm_source=softwareontheweb.com"
                       target="_blank" rel="noopener nofollow noreferrer"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Visit
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <button type="button"
                            x-data="upvote({{ json_encode($product->is_upvoted_by_current_user) }}, {{ $product->votes_count }}, {{ $product->id }}, '{{ $product->slug }}', {{ Auth::check() ? 'true' : 'false' }}, '{{ csrf_token() }}')"
                            @click="toggleUpvote()"
                            :class="{ 'text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 border-transparent': isUpvoted, 'text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 border-gray-300': !isUpvoted }"
                            class="inline-flex items-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                        </svg>
                        Upvote <span class="ml-2" :class="{ 'text-primary-100': isUpvoted, 'text-gray-500': !isUpvoted }" x-text="votesCount"></span>
                    </button>
                </div>
            </div>

            {{-- Description --}}
            <div class="prose max-w-none mb-6 text-gray-700">
                {!! $product->description ?: 'No description available.' !!}
            </div>
 
            @php
                $generalCategories = $product->categories->filter(function ($cat) {
                    return !$cat->types->contains('name', 'Pricing');
                });
                $pricingCategories = $product->categories->filter(function ($cat) {
                    return $cat->types->contains('name', 'Pricing');
                });
            @endphp
 
            {{-- Software Categories (excluding Pricing) --}}
            @if($generalCategories->count() > 0 || $pricingCategories->count() > 0 || $product->pricing_type || ($product->price && is_numeric($product->price) && $product->price > 0))
                <div class="mb-6 text-sm text-gray-700">
                    @if($generalCategories->count() > 0)
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span class="font-semibold">Categories:</span>
                            @foreach($generalCategories as $category)
                                <a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="text-primary-600 hover:underline">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    @if($pricingCategories->count() > 0)
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span class="font-semibold">Pricing Categories:</span>
                            @foreach($pricingCategories as $category)
                                <a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="text-primary-600 hover:underline">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    @if($product->pricing_type || ($product->price && is_numeric($product->price) && $product->price > 0))
                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="font-semibold">Pricing:</span>
                            <span>
                                @if($product->pricing_type)
                                    <span>{{ $product->pricing_type }}</span>
                                @endif
                                @if($product->pricing_type && $product->price && is_numeric($product->price) && $product->price > 0)
                                    <span> - </span>
                                @endif
                                @if($product->price && is_numeric($product->price) && $product->price > 0)
                                    <span>${{ number_format($product->price, 2) }}</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-gray-500 italic mb-6">No category or pricing information available.</p>
            @endif

            @if(Auth::check() && (Auth::id() === $product->user_id || Auth::user()->hasRole('admin')))
                <x-slot:actions>
                    <a href="{{ route('products.edit', $product) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Edit Product
                    </a>
                </x-slot:actions>
            @endif
        </div>
    </div>
</x-main-content-layout>
>>>>>>> REPLACE
</diff>
</apply_diff>
