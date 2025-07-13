@php $mainContentMaxWidth = 'max-w-full'; @endphp
@extends('layouts.app')

@section('header-title')
    <div class="flex items-center pt-1.5">
        <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-700 mr-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-lg md:text-base font-semibold tracking-tight">{{ $title }} details</h1>
    </div>
@endsection

@section('content')
<div class="bg-white rounded-lg p-6 md:p-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2">
            <div class="flex items-center mb-4">
                @if($product->logo)
                    <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="{{ $product->name }} logo" class="w-16 h-16 object-contain rounded-lg mr-4">
                @elseif($product->link)
                    <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="w-16 h-16 object-contain rounded-lg mr-4">
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                    <p class="text-gray-600">{{ $product->product_page_tagline }}</p>
                    <div class="flex flex-wrap items-center mt-2">
                        @php
                            $generalCategories = $product->categories->filter(function ($cat) {
                                return !$cat->types->contains('name', 'Pricing');
                            });
                        @endphp
                        @foreach($generalCategories as $category)
                            <a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="text-xs text-gray-500 hover:underline">{{ $category->name }}</a>
                            @if(!$loop->last)
                                <span class="text-gray-400 mx-2">&middot;</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    @unless($product->user->hasRole('admin'))
                        <img src="{{ $product->user->avatar() }}" alt="{{ $product->user->name }}" class="w-8 h-8 rounded-full mr-3">
                        <span class="text-gray-700">{{ $product->user->name }}</span>
                    @endunless
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ $product->link . (strpos($product->link, '?') === false ? '?' : '&') }}utm_source=softwareontheweb.com"
                       target="_blank" rel="noopener nofollow noreferrer"
                       class="inline-flex items-center px-4 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Visit
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    @livewire('product-upvote-button', ['product' => $product])
                </div>
            </div>

            <div class="prose max-w-none">
                {!! $product->description !!}
            </div>
        </div>
        <div class="md:col-span-1">
            @section('right_sidebar_content')
            <div class="space-y-6 px-8 py-8">
                @if($pricingCategory)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-2">Pricing Model</h3>
                        <p class="text-xs text-gray-600">{{ $pricingCategory->name }}</p>
                    </div>
                @endif

                @if($similarProducts->isNotEmpty())
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 mb-2">Similar Products</h3>
                        <ul class="space-y-5">
                            @foreach($similarProducts as $similarProduct)
                                <li class="flex items-start">
                                    <a href="{{ route('products.show', $similarProduct->slug) }}">
                                        @if($similarProduct->logo)
                                            <img src="{{ Str::startsWith($similarProduct->logo, 'http') ? $similarProduct->logo : asset('storage/' . $similarProduct->logo) }}" alt="{{ $similarProduct->name }} logo" class="w-10 h-10 object-contain rounded-lg mr-2 bottom-2">
                                        @elseif($similarProduct->link)
                                            <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($similarProduct->link) }}" alt="{{ $similarProduct->name }} favicon" class="w-10 h-10 object-contain rounded-lg mr-2">
                                        @endif
                                    </a>
                                    <div class="flex-1">
                                        <a href="{{ route('products.show', $similarProduct->slug) }}" class="text-sm font-semibold text-gray-700 hover:text-primary-500">{{ $similarProduct->name }}</a>
                                        <p class="text-xs text-gray-600 mt-0">{{ str::limit($similarProduct->tagline, 40) }}</p>
                                        <!-- <div class="text-xs text-gray-500 mt-2">
                                            <span class="font-bold">{{ $similarProduct->votes_count }}</span> Upvotes
                                        </div> -->
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            @endsection
        </div>
    </div>
</div>
@endsection
