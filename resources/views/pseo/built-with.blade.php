@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-4xl', 'headerPadding' => 'px-4 md:pt-4'])

@section('title', $title)
@section('meta_description', $metaDescription)
@section('robots', 'index, follow')

@section('header-title')
    {{ $title }}
@endsection

@section('canonical')
    <link rel="canonical" href="{{ route('pseo.builtWith', $techstack->slug) }}" />
@endsection

@section('content')
    <script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url('/'),
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Built with '.$techstack->name,
            'item' => route('pseo.builtWith', $techstack->slug),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <div class="relative z-10 flex-shrink-0" style="background-color: var(--color-body-bg, #ffffff);">
        <div class="px-4 pb-4 pt-4 md:pt-2 lg:pt-0">
            <p class="text-sm text-gray-800">
                Browse {{ $products->count() }} products built with {{ $techstack->name }}, ranked by community activity.
            </p>
        </div>
    </div>

    <div class="flex min-h-[400px] flex-1 flex-col" style="background-color: var(--color-body-bg, #ffffff);">
        <div class="md:space-y-1">
            @include('partials.products_list', [
                'regularProducts' => $products,
                'promotedProducts' => collect(),
            ])
        </div>
    </div>
@endsection
