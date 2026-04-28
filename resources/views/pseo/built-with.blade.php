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
            ['label' => 'Built with ' . $techstack->name],
        ]" />
    </div>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
            {"@type": "ListItem", "position": 2, "name": "Built with {{ $techstack->name }}"}
        ]
    }
    </script>

    <div class="bg-white rounded-lg py-6 md:py-8">
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                @if($techstack->icon)
                    <img src="{{ $techstack->icon }}" alt="{{ $techstack->name }}" class="w-8 h-8 object-contain">
                @endif
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $title }}</h1>
            </div>
            <p class="text-gray-500 text-sm">{{ $products->count() }} products · Ranked by community votes</p>
        </div>

        @if($products->isEmpty())
            <p class="text-gray-500">No products found using this technology yet.</p>
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
                            @php
                                $pricingCat = $product->categories->first(fn($c) => $c->types->contains('name', 'Pricing'));
                            @endphp
                            @if($pricingCat)
                                <span class="mt-1.5 inline-block text-xs bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">{{ $pricingCat->name }}</span>
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
            <h3 class="text-sm font-semibold text-gray-700 mb-2">About {{ $techstack->name }}</h3>
            <p class="text-xs text-gray-500">{{ $products->count() }} products in our directory are built with {{ $techstack->name }}.</p>
        </div>
    </div>
@endsection
