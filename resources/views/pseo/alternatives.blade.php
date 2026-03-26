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
        @php
            $breadcrumbs = [
                ['label' => $product->name, 'url' => route('products.show', $product->slug)],
                ['label' => 'Alternatives'],
            ];
        @endphp
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
            {"@type": "ListItem", "position": 2, "name": "{{ $product->name }}", "item": "{{ route('products.show', $product->slug) }}"},
            {"@type": "ListItem", "position": 3, "name": "Alternatives to {{ $product->name }}"}
        ]
    }
    </script>

    <div class="bg-white rounded-lg py-6 md:py-8">
        {{-- Original product card --}}
        <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-200 flex items-center gap-4">
            <img src="{{ $product->logo_url }}" alt="{{ $product->name }}" class="w-14 h-14 rounded-xl object-cover border border-gray-100 flex-shrink-0">
            <div>
                <p class="text-xs text-gray-400 uppercase font-semibold tracking-wide mb-0.5">You're looking for alternatives to</p>
                <a href="{{ route('products.show', $product->slug) }}" class="text-lg font-bold text-gray-900 hover:text-primary-600">{{ $product->name }}</a>
                <p class="text-sm text-gray-500">{{ $product->tagline }}</p>
            </div>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">{{ $title }}</h1>
        <p class="text-gray-500 text-sm mb-6">{{ $alternatives->count() }} alternatives found</p>

        <?php if($alternatives->isEmpty()): ?>
            <p class="text-gray-500">No alternatives found yet. Check back as new products are added.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach($alternatives as $index => $alt): ?>
                    <div class="flex items-start gap-4 p-4 border border-gray-100 rounded-xl hover:border-gray-200 hover:shadow-sm transition-all">
                        <span class="text-sm font-bold text-gray-300 w-5 mt-1 flex-shrink-0">{{ $index + 1 }}</span>
                        <div class="flex-shrink-0">
                            <img src="{{ $alt->logo_url }}" alt="{{ $alt->name }}" class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('products.show', $alt->slug) }}" class="text-base font-semibold text-gray-900 hover:text-primary-600">
                                {{ $alt->name }}
                            </a>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $alt->tagline }}</p>
                            <div class="mt-2">
                                <a href="{{ route('pseo.compare', ['params' => $product->slug . '-vs-' . $alt->slug]) }}"
                                   class="text-xs text-primary-600 hover:underline">
                                    Compare {{ $product->name }} vs {{ $alt->name }} &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
@endsection

@section('right_sidebar_content')
    <div class="hidden md:block space-y-4">
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">About {{ $product->name }}</h3>
            <p class="text-xs text-gray-500">{{ Str::limit(strip_tags($product->tagline ?? ''), 120) }}</p>
            <a href="{{ route('products.show', $product->slug) }}" class="mt-3 block text-xs text-primary-600 hover:underline">
                View {{ $product->name }} &rarr;
            </a>
        </div>
    </div>
@endsection
