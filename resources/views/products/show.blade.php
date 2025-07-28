@php $mainContentMaxWidth = 'max-w-full'; @endphp
@extends('layouts.app')

@section('title', $pageTitle)

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
    <div class="grid grid-cols-1 md:grid-cols-1 gap-8">
        <div class="md:col-span-2">
            <div class="flex items-center mb-4">
                @if($product->logo)
                    <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="{{ $product->name }} logo" class="size-20 object-contain rounded-lg mr-3">
                @elseif($product->link)
                    <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="size-20 object-contain rounded-lg mr-3">
                @endif
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                    <p class="text-gray-800 text-base">{{ $product->product_page_tagline }}</p>
                    <div class="flex flex-wrap items-center mt-1">
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
                    <div class="flex flex-col gap-1">
                        <div class="text-xs font-medium">
                           Publisher
                        </div>
                        <div class="flex flex-row">
                            <div>
                                 <img src="{{ $product->user->avatar() }}" alt="{{ $product->user->name }}" class="size-5 rounded-full mr-1 border">
                          </div>
                            <div class="text-gray-700 text-xs content-center"> 
                                {{ $product->user->name }}
                            </div>
                            
                        </div>
                    </div>
                        
                       
                    @endunless
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ $product->link . (strpos($product->link, '?') === false ? '?' : '&') }}utm_source=softwareontheweb.com"
                       target="_blank" rel="noopener nofollow noreferrer"
                       class="inline-flex items-center px-4 py-1.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Visit Website &nbsp;
                        <svg class="size-4 stroke-gray-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M7 17L17 7M17 7H8M17 7V16" stroke="" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                    </a>
                    @livewire('product-upvote-button', ['product' => $product])
                </div>
            </div>

            @if($product->video_url)
                <div class="mb-6">
                    <div class="aspect-w-16 aspect-h-9">
                        <iframe src="{{ $product->getEmbedUrl() }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            @endif

            <div class="prose max-w-none">
                {!! $product->description !!}
            </div>

            <div class="md:hidden mt-8">
                @include('products.partials._sidebar-info')
            </div>
        </div>
        <div class="hidden md:block md:col-span-1">
            @section('right_sidebar_content')
                @include('products.partials._sidebar-info')
            @endsection
        </div>
    </div>
</div>
@endsection
