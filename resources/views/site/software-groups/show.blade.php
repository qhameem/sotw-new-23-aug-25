@extends('layouts.app', [
    'mainContentMaxWidth' => 'max-w-4xl',
    'containerMaxWidth' => 'max-w-[96rem]',
    'headerPadding' => 'px-4 md:pt-4',
])

@section('title', $broadCategory['label'].' Software & Tools'.($currentPage > 1 ? ' - Page '.$currentPage : '').' | Software on the Web')
@section('meta_description', $broadCategory['description'].' Compare curated products, then narrow the list by subcategory.'.($currentPage > 1 ? ' Page '.$currentPage.'.' : ''))
@section('canonical')
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if($currentPage > 1)
        <link rel="prev" href="{{ $currentPage === 2 ? route('software-groups.show', ['group' => $broadCategory['key']]) : route('software-groups.page', ['group' => $broadCategory['key'], 'page' => $currentPage - 1]) }}">
    @endif
    @if($currentPage < $lastPage)
        <link rel="next" href="{{ route('software-groups.page', ['group' => $broadCategory['key'], 'page' => $currentPage + 1]) }}">
    @endif
@endsection

@section('hide_desktop_page_header', '1')

@section('left_sidebar_content')
    @include('products.partials._browse')
@endsection

@section('content')
    <script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Software', 'item' => route('categories.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $broadCategory['label'], 'item' => route('software-groups.show', ['group' => $broadCategory['key']])],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <header class="border-b border-gray-100 px-4 pb-5 pt-6">
        <nav class="mb-4 flex items-center gap-2 text-xs text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" wire:navigate.hover class="hover:text-primary-600">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('categories.index') }}" wire:navigate.hover class="hover:text-primary-600">Software</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="text-gray-800">{{ $broadCategory['label'] }}</span>
        </nav>

        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-500">{{ $broadCategory['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-950">{{ $broadCategory['label'] }} Software &amp; Tools</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">{{ $broadCategory['description'] }} Compare curated products below or choose a specific subcategory.</p>

        <nav class="mt-5 flex gap-2 overflow-x-auto pb-1 scrollbar-hide" aria-label="{{ $broadCategory['label'] }} subcategories">
            @foreach($broadCategory['items'] as $subcategory)
                <a
                    href="{{ $subcategory['url'] }}"
                    wire:navigate.hover
                    class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700"
                >
                    {{ $subcategory['name'] }}
                    <span class="ml-1.5 text-[10px] font-medium text-gray-400">{{ $subcategory['count'] }}</span>
                </a>
            @endforeach
        </nav>
    </header>

    <section aria-label="{{ $broadCategory['label'] }} products">
        @include('partials.products_list', [
            'regularProducts' => $regularProducts,
            'promotedProducts' => $promotedProducts,
            'showProductEngagement' => false,
        ])
    </section>

    @if($lastPage > 1)
        <nav class="flex items-center justify-between border-t border-gray-100 px-4 py-6 text-sm" aria-label="Product pages">
            @if($currentPage > 1)
                <a href="{{ $currentPage === 2 ? route('software-groups.show', ['group' => $broadCategory['key']]) : route('software-groups.page', ['group' => $broadCategory['key'], 'page' => $currentPage - 1]) }}" wire:navigate.hover class="font-semibold text-primary-600 hover:text-primary-700">← Previous</a>
            @else
                <span></span>
            @endif
            <span class="text-xs text-gray-500">Page {{ $currentPage }} of {{ $lastPage }}</span>
            @if($currentPage < $lastPage)
                <a href="{{ route('software-groups.page', ['group' => $broadCategory['key'], 'page' => $currentPage + 1]) }}" wire:navigate.hover class="font-semibold text-primary-600 hover:text-primary-700">Next →</a>
            @endif
        </nav>
    @endif
@endsection
