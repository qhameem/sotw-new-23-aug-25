@extends('layouts.app')

@section('title')
    All Categories
@endsection

@section('header-title')
    All Categories
@endsection

@section('content')
    @php
        $categoryGroups = collect($categoryNavigationGroups ?? []);
        $supercategories = $categoryGroups->reject(fn ($group) => $group['key'] === 'view-all');
        $allCategoriesGroup = $categoryGroups->firstWhere('key', 'view-all');
        $allCategories = collect($allCategoriesGroup['items'] ?? []);
        $breadcrumbSchema = [
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
                    'name' => 'All Categories',
                    'item' => route('categories.index'),
                ],
            ],
        ];
    @endphp

    <div class="py-4">
        <x-breadcrumbs :items="[['label' => 'All Categories']]" />
    </div>

    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <div class="space-y-12 p-4">
        <section class="overflow-hidden rounded-[2rem] border border-gray-200 bg-gradient-to-br from-slate-50 via-white to-slate-100">
            <div class="px-6 py-8 sm:px-8">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.28em] text-gray-400">Category Directory</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-gray-900">Browse categories by user goal</h1>
                <p class="mt-4 max-w-3xl text-sm leading-7 text-gray-600">Skip the endless dropdown. Start with a parent group, then jump into the exact sub-category you need. Every category is also available in an alphabetical directory below.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($supercategories as $group)
                        <a href="#{{ $group['key'] }}"
                            class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:border-gray-300 hover:text-gray-900">
                            {{ $group['label'] }}
                        </a>
                    @endforeach
                    <a href="#all-categories"
                        class="inline-flex items-center rounded-full bg-gray-900 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-gray-800">
                        View all categories
                    </a>
                </div>
            </div>
        </section>

        @foreach ($supercategories as $group)
            <section id="{{ $group['key'] }}" class="scroll-mt-28 space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.28em] text-gray-400">{{ $group['eyebrow'] }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-gray-900">{{ $group['label'] }}</h2>
                        <p class="mt-2 max-w-3xl text-sm leading-7 text-gray-600">{{ $group['description'] }}</p>
                    </div>
                    <div class="text-sm font-medium text-gray-500">
                        {{ $group['item_count'] }} {{ \Illuminate\Support\Str::plural('category', $group['item_count']) }}
                    </div>
                </div>

                @if (!empty($group['items']))
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($group['items'] as $category)
                            <a href="{{ $category['url'] }}"
                                class="group flex items-start justify-between rounded-2xl border border-gray-200 bg-white px-4 py-4 transition hover:border-gray-300 hover:bg-slate-50">
                                <span class="pr-4">
                                    <span class="block text-sm font-semibold text-gray-900">{{ $category['name'] }}</span>
                                    <span class="mt-1 block text-xs text-gray-500">{{ $category['count'] }} products</span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition group-hover:text-gray-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-slate-50 px-5 py-8 text-center">
                        <p class="text-sm font-medium text-gray-900">No categories are in this group yet.</p>
                        <p class="mt-2 text-sm text-gray-600">Check the alphabetical list below to browse everything currently available.</p>
                    </div>
                @endif
            </section>
        @endforeach

        <section id="all-categories" class="scroll-mt-28 space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.28em] text-gray-400">Alphabetical Directory</p>
                    <h2 class="mt-2 text-2xl font-semibold text-gray-900">View All Categories</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-gray-600">This list includes every category in the system, including software categories, pricing models, and best-for tags.</p>
                </div>
                <div class="text-sm font-medium text-gray-500">
                    {{ $allCategories->count() }} {{ \Illuminate\Support\Str::plural('category', $allCategories->count()) }}
                </div>
            </div>

            @if ($allCategories->isEmpty())
                <div class="rounded-2xl border border-dashed border-gray-300 bg-slate-50 px-5 py-8 text-center">
                    <p class="text-sm font-medium text-gray-900">No categories are currently available.</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($allCategories as $category)
                        <a href="{{ $category['url'] }}"
                            class="group flex items-start justify-between rounded-2xl border border-gray-200 bg-white px-4 py-4 transition hover:border-gray-300 hover:bg-slate-50">
                            <span class="pr-4">
                                <span class="block text-sm font-semibold text-gray-900">{{ $category['name'] }}</span>
                                <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                    <span>{{ $category['count'] }} products</span>
                                    @if (!empty($category['type_label']))
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                                            {{ $category['type_label'] }}
                                        </span>
                                    @endif
                                </span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="mt-0.5 h-4 w-4 shrink-0 text-gray-400 transition group-hover:text-gray-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
