@extends('layouts.app', [
    'mainContentMaxWidth' => 'max-w-4xl',
    'containerMaxWidth' => 'max-w-[96rem]',
    'headerPadding' => 'px-4 md:px-0',
])

@section('title', 'All products | '.config('app.name'))
@section('header-title', 'All products')
@section('meta_description', 'Browse all published software products by category and publication date.')

@section('before_header_title')
    <x-breadcrumbs :items="[['label' => 'Products']]" />
@endsection

@section('left_sidebar_content')
    @include('products.partials._browse')
@endsection

@section('content')
    <div class="space-y-4 pb-8">
        <div class="md:hidden">
            <x-breadcrumbs :items="[['label' => 'Products']]" />
        </div>

        <form action="{{ route('products.catalog') }}" method="GET"
            class="grid grid-cols-2 items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 lg:grid-cols-3">
            @foreach (['category' => ['Category', 'All categories'], 'platform' => ['Platform', 'All platforms'], 'pricing' => ['Pricing', 'All pricing'], 'use_case' => ['Use Case', 'All use cases']] as $key => [$label, $placeholder])
                <label class="min-w-0 text-sm font-medium text-gray-700">
                    {{ $label }}
                    <select name="{{ $key }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
                        <option value="">{{ $placeholder }}</option>
                        @foreach ($filterOptions[$key] as $option)
                            <option value="{{ $option->slug }}" @selected(($filters[$key] ?? null) === $option->slug)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach
            <button type="submit" class="justify-self-start rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">Apply</button>
        </form>

        <p class="text-sm text-gray-600">{{ number_format($products->total()) }} published {{ Str::plural('product', $products->total()) }}</p>

        @if ($products->isNotEmpty())
            @include('partials.products_list', ['regularProducts' => $products, 'showPublicationDate' => true, 'paginationView' => 'products.catalog-pagination'])
        @else
            <p class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600">No products found.</p>
        @endif
    </div>
@endsection
