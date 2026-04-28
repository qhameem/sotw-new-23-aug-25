@php
    $mainContentMaxWidth = 'max-w-7xl';
@endphp
@extends('layouts.app')

@section('title', $title)
@section('meta_description', $metaDescription)
@section('robots', 'index, follow')

@section('content')
    <div class="py-4">
        <x-breadcrumbs :items="[
            ['label' => $category->name, 'link' => route('categories.show', $category->slug)],
            ['label' => 'Best for ' . $bestfor->name],
        ]" />
    </div>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
            {"@type": "ListItem", "position": 2, "name": "{{ $category->name }}", "item": "{{ route('categories.show', $category->slug) }}"},
            {"@type": "ListItem", "position": 3, "name": "Best for {{ $bestfor->name }}"}
        ]
    }
    </script>

    <div class="bg-white rounded-lg py-6 md:py-8">
        <div class="mb-6">
            <div class="flex flex-wrap gap-2 mb-2 text-sm text-gray-500">
                <a href="{{ route('categories.show', $category->slug) }}" class="hover:text-primary-600">{{ $category->name }}</a>
                <span>·</span>
                <span class="font-medium text-gray-700">Best for {{ $bestfor->name }}</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $title }}</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $products->count() }} tools · Community ranked</p>
        </div>

        @if($products->isEmpty())
            <p class="text-gray-500">No products found for this combination.</p>
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
            <h3 class="text-sm font-semibold text-gray-700 mb-2">More {{ $category->name }} Tools</h3>
            <a href="{{ route('pseo.best', $category->slug) }}" class="text-xs text-primary-600 hover:underline block mb-1">
                Best {{ $category->name }} Software →
            </a>
            <a href="{{ route('categories.show', $category->slug) }}" class="text-xs text-primary-600 hover:underline block">
                All {{ $category->name }} products →
            </a>
        </div>
    </div>
@endsection
