@php
    $mainContentMaxWidth = 'max-w-7xl';
    $mainPadding = 'px-6 sm:px-6 lg:px-8';
    $headerPadding = 'px-6 sm:px-6 lg:px-8';
@endphp
@extends('layouts.app')

@section('title', $title)
@section('meta_description', $metaDescription)
@section('robots', 'index, follow')

@section('content')
    <div class="py-4">
        <x-breadcrumbs :items="[
            ['label' => $pricing->name . ' Software'],
        ]" />
    </div>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
            {"@type": "ListItem", "position": 2, "name": "{{ $pricing->name }} Software"}
        ]
    }
    </script>

    <div class="bg-white rounded-lg py-6 md:py-8">
        <div class="mb-6">
            <span class="inline-block text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-3 py-1 rounded-full mb-3">{{ $pricing->name }}</span>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $title }}</h1>
            @if($pricing->meta_description)
                <p class="mt-2 text-gray-600 text-sm">{{ $pricing->meta_description }}</p>
            @endif
            <p class="mt-1 text-sm text-gray-400">{{ $products->count() }} tools · Ranked by community</p>
        </div>

        @if($products->isEmpty())
            <p class="text-gray-500">No products found in this pricing model yet.</p>
        @else
            <div class="space-y-4">
                @foreach($products as $index => $product)
                    <div class="flex items-start gap-4 p-4 border border-gray-100 rounded-xl hover:border-gray-200 hover:shadow-sm transition-all">
                        <span class="text-sm font-bold text-gray-300 w-5 mt-1 flex-shrink-0">{{ $index + 1 }}</span>
                        <div class="flex-shrink-0">
                            <img src="{{ $product->logo_url }}" alt="{{ $product->name }}" class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('products.show', $product->slug) }}" class="text-base font-semibold text-gray-900 hover:text-primary-600">
                                {{ $product->name }}
                            </a>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $product->tagline }}</p>
                            @php $softCats = $product->categories->filter(fn($c) => $c->types->contains('name', 'Category'))->take(2); @endphp
                            @if($softCats->isNotEmpty())
                                <div class="flex gap-1 mt-1.5">
                                    @foreach($softCats as $cat)
                                        <a href="{{ route('categories.show', $cat->slug) }}" class="text-xs text-gray-500 hover:text-primary-600 hover:underline">{{ $cat->name }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="flex-shrink-0 text-center">
                            <span class="text-sm font-bold text-gray-700">{{ $product->votes_count }}</span>
                            <p class="text-xs text-gray-400">votes</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('right_sidebar_content')
    <div class="hidden md:block space-y-4">
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Pricing Model</h3>
            <p class="text-xs text-gray-500">Showing {{ $products->count() }} {{ $pricing->name }} tools.</p>
        </div>
    </div>
@endsection
