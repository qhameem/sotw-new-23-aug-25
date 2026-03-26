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
                ['label' => $productA->name, 'url' => route('products.show', $productA->slug)],
                ['label' => 'vs ' . $productB->name],
            ];
        @endphp
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>

    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "BreadcrumbList",
        "itemListElement": [
            {"@@type": "ListItem", "position": 1, "name": "Home", "item": "{{ url('/') }}"},
            {"@@type": "ListItem", "position": 2, "name": "{{ $productA->name }}", "item": "{{ route('products.show', $productA->slug) }}"},
            {"@@type": "ListItem", "position": 3, "name": "vs {{ $productB->name }}"}
        ]
    }
    </script>

    <div class="bg-white rounded-lg py-6 md:py-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6">{{ $title }}</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach([$productA, $productB] as $p)
                @php
                    $pricingCat = null;
                    $softCats = collect();
                    foreach ($p->categories as $cat) {
                        $typeNames = $cat->types->pluck('name');
                        if ($typeNames->contains('Pricing') && !$pricingCat) {
                            $pricingCat = $cat;
                        }
                        if ($typeNames->contains('Software')) {
                            $softCats->push($cat);
                        }
                    }
                    $softCats = $softCats->take(2);
                @endphp
                <div class="border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ $p->logo_url }}" alt="{{ $p->name }}" class="w-14 h-14 rounded-xl object-cover border border-gray-100 flex-shrink-0">
                        <div>
                            <a href="{{ route('products.show', $p->slug) }}" class="text-lg font-bold text-gray-900 hover:text-primary-600">
                                {{ $p->name }}
                            </a>
                            <p class="text-sm text-gray-500">{{ $p->tagline }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        
                        <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                            <span class="text-gray-500 text-xs">Pricing</span>
                            @if($pricingCat)
                                <a href="{{ route('pseo.pricing', $pricingCat->slug) }}" class="text-xs font-medium text-green-700 bg-green-50 border border-green-200 px-2 py-0.5 rounded-full hover:underline">
                                    {{ $pricingCat->name }}
                                </a>
                            @else
                                <span class="text-xs text-gray-400">&mdash;</span>
                            @endif
                        </div>

                        
                        <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                            <span class="text-gray-500 text-xs">Community votes</span>
                            <span class="text-xs font-bold text-gray-800">{{ number_format($p->votes_count) }}</span>
                        </div>

                        
                        @if($p->techStacks->isNotEmpty())
                            <div class="flex justify-between items-start border-b border-gray-50 pb-2">
                                <span class="text-gray-500 text-xs flex-shrink-0">Built with</span>
                                <div class="flex flex-wrap gap-1 justify-end">
                                    @foreach($p->techStacks->take(3) as $stack)
                                        <a href="{{ route('pseo.builtWith', $stack->slug) }}" class="text-xs text-gray-600 bg-gray-100 px-1.5 py-0.5 rounded hover:bg-gray-200">
                                            {{ $stack->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        
                        @if($softCats->isNotEmpty())
                            <div class="flex justify-between items-start pb-1">
                                <span class="text-gray-500 text-xs flex-shrink-0">Category</span>
                                <div class="flex flex-wrap gap-1 justify-end">
                                    @foreach($softCats as $cat)
                                        <a href="{{ route('categories.show', $cat->slug) }}" class="text-xs text-gray-600 hover:underline">
                                            {{ $cat->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('products.show', $p->slug) }}" class="flex-1 text-center text-xs font-medium text-white px-3 py-1.5 rounded-lg hover:opacity-90 transition-opacity" style="background-color: var(--color-primary-500, #6366f1);">
                            View {{ $p->name }}
                        </a>
                        <a href="{{ route('pseo.alternatives', $p->slug) }}" class="flex-1 text-center text-xs font-medium border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                            Alternatives
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('right_sidebar_content')
    <div class="hidden md:block space-y-4">
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Explore more</h3>
            <a href="{{ route('pseo.alternatives', $productA->slug) }}" class="block text-xs text-primary-600 hover:underline mb-2">
                Alternatives to {{ $productA->name }} &rarr;
            </a>
            <a href="{{ route('pseo.alternatives', $productB->slug) }}" class="block text-xs text-primary-600 hover:underline">
                Alternatives to {{ $productB->name }} &rarr;
            </a>
        </div>
    </div>
@endsection
