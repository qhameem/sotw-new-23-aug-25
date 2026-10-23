@extends('layouts.app', ['hideSidebar' => true, 'mainContentMaxWidth' => 'max-w-6xl'])

@section('title', 'Software Alternatives and Comparisons'.($products->currentPage() > 1 ? ' — Page '.$products->currentPage() : '').' | Software on the Web')
@section('meta_description', 'Find software alternatives and relevant side-by-side comparisons. Explore published products by name and compare options for your workflow.')
@section('hide_desktop_page_header', '1')
@section('robots', $search !== '' || $entries->isEmpty() ? 'noindex, follow' : 'index, follow, max-image-preview:large')
@section('canonical')
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endsection

@section('content')
    <div class="px-4 py-8 sm:px-6">
        <x-breadcrumbs :items="[['label' => 'Software alternatives']]" />
        <header class="my-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-950">Software alternatives and comparisons</h1>
            <p class="mt-3 max-w-3xl text-gray-600">Find alternatives to the tools you know. Compare relevant products by features, audience, pricing, and workflow.</p>
            <p class="mt-2 max-w-3xl text-sm text-gray-500">Guides appear when relevant matches are available. Recommendations use editorial selections and shared product categories; unrelated comparisons are excluded.</p>
            <form action="{{ route('alternatives.index') }}" method="get" class="mt-6 flex max-w-lg gap-2">
                <label for="alternatives-search" class="sr-only">Search software by name</label>
                <input id="alternatives-search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search software by name" class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-2">
                <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2 font-semibold text-white hover:bg-primary-700">Search</button>
            </form>
            @if($search !== '')
                <a href="{{ route('alternatives.index') }}" class="mt-3 inline-block text-sm text-primary-700 hover:underline">Clear search</a>
            @endif
        </header>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse($entries as $entry)
                <article class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h2 class="text-lg font-semibold text-gray-900"><a href="{{ route('products.show', $entry['product']->slug) }}" class="hover:text-primary-700">{{ $entry['product']->name }}</a></h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ \Illuminate\Support\Str::limit(strip_tags($entry['product']->tagline), 140) }}</p>
                    <ul class="mt-4 space-y-3">
                        @foreach($entry['links'] as $link)
                            <li><a href="{{ $link['url'] }}" class="text-sm font-semibold text-primary-700 hover:underline">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </article>
            @empty
                <p class="col-span-full rounded-xl border border-gray-200 p-6 text-gray-600">No matching guides on this page. Try another product name or browse another page.</p>
            @endforelse
        </div>

        @if($products->hasPages())
            <nav class="mt-8 flex items-center justify-between gap-4 border-t border-gray-200 py-5 text-sm" aria-label="Alternatives directory pages">
                <div>
                    @if($products->previousPageUrl())
                        <a href="{{ $products->currentPage() === 2 && $search === '' ? route('alternatives.index') : $products->previousPageUrl() }}" class="font-semibold text-primary-700 hover:underline">Previous</a>
                    @endif
                </div>
                <span aria-current="page" class="text-gray-500">Page {{ $products->currentPage() }}</span>
                <div>
                    @if($products->nextPageUrl())
                        <a href="{{ $products->nextPageUrl() }}" class="font-semibold text-primary-700 hover:underline">Next</a>
                    @endif
                </div>
            </nav>
        @endif
    </div>
@endsection
