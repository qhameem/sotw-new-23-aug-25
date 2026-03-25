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
    {{-- Breadcrumbs --}}
    <div class="py-4">
        <x-breadcrumbs :items="[
            ['label' => 'Topics', 'url' => route('topics.index')],
            ['label' => $category->name, 'url' => route('categories.show', $category->slug)],
            ['label' => 'Best of'],
        ]" />
    </div>

    {{-- JSON-LD BreadcrumbList --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
            {"@type": "ListItem", "position": 2, "name": "{{ $category->name }}", "item": "{{ route('categories.show', $category->slug) }}"},
            {"@type": "ListItem", "position": 3, "name": "Best {{ $category->name }} Software"}
        ]
    }
    </script>

    <div class="bg-white rounded-lg py-6 md:py-8">
        <div class="mb-6">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $title }}</h1>
            @if($category->meta_description)
                <p class="mt-2 text-gray-600">{{ $category->meta_description }}</p>
            @endif
            <p class="mt-1 text-sm text-gray-400">{{ $products->count() }} tools · Ranked by community votes</p>
        </div>

        @if($products->isEmpty())
            <p class="text-gray-500">No products found in this category yet.</p>
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
                            <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $product->tagline }}</p>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @php
                                    $pricingCat = $product->categories->first(fn($c) => $c->types->contains('name', 'Pricing'));
                                @endphp
                                @if($pricingCat)
                                    <span class="text-xs bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">{{ $pricingCat->name }}</span>
                                @endif
                            </div>
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
            <h3 class="text-sm font-semibold text-gray-700 mb-3">About {{ $category->name }}</h3>
            @if($category->meta_description)
                <p class="text-xs text-gray-500">{{ $category->meta_description }}</p>
            @endif
            <a href="{{ route('categories.show', $category->slug) }}" class="mt-3 block text-xs text-primary-600 hover:underline">
                Browse all {{ $category->name }} products →
            </a>
        </div>
    </div>
@endsection
