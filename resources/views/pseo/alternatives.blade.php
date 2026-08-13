@php
    $mainContentMaxWidth = 'max-w-7xl';
    $pageUrl = route('pseo.alternatives', ['product' => $product->slug]);
    $topAlternatives = $alternatives->take(5)->values();

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            [
                '@type' => 'WebPage',
                '@id' => $pageUrl . '#webpage',
                'url' => $pageUrl,
                'name' => $title,
                'description' => $metaDescription,
                'about' => [
                    '@type' => 'SoftwareApplication',
                    'name' => $product->name,
                    'url' => route('products.show', ['product' => $product->slug]),
                ],
            ],
            [
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
                        'name' => $product->name,
                        'item' => route('products.show', ['product' => $product->slug]),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => "Alternatives to {$product->name}",
                    ],
                ],
            ],
            $topAlternatives->isNotEmpty() ? [
                '@type' => 'ItemList',
                'name' => "Best {$product->name} alternatives",
                'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
                'numberOfItems' => $topAlternatives->count(),
                'itemListElement' => $topAlternatives->values()->map(function ($alt, $index) use ($product) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'url' => route('products.show', ['product' => $alt->slug]),
                        'name' => $alt->name,
                        'description' => $alt->decision_summary ?: $alt->match_summary,
                        'item' => [
                            '@type' => 'SoftwareApplication',
                            'name' => $alt->name,
                            'url' => route('products.show', ['product' => $alt->slug]),
                            'applicationCategory' => $alt->primary_category_label,
                            'offers' => !empty($alt->pricing_label) && $alt->pricing_label !== 'Pricing not listed'
                                ? [
                                    '@type' => 'Offer',
                                    'category' => $alt->pricing_label,
                                  ]
                                : null,
                            'additionalProperty' => [
                                [
                                    '@type' => 'PropertyValue',
                                    'name' => 'Alternative to',
                                    'value' => $product->name,
                                ],
                            ],
                        ],
                    ];
                })->map(function ($item) {
                    if (isset($item['item']['offers']) && $item['item']['offers'] === null) {
                        unset($item['item']['offers']);
                    }

                    return $item;
                })->all(),
            ] : null,
            !empty($faqItems) ? [
                '@type' => 'FAQPage',
                'mainEntity' => collect($faqItems)->map(function ($item) {
                    return [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $item['answer'],
                        ],
                    ];
                })->values()->all(),
            ] : null,
        ])),
    ];
@endphp
@extends('layouts.app')

@section('title', $title)
@section('meta_description', $metaDescription)
@section('robots', !empty($shouldNoindex) ? 'noindex, follow' : 'index, follow, max-image-preview:large')
@section('hide_desktop_page_header', '1')
@section('header-title', '')

@section('canonical')
    <link rel="canonical" href="{{ $pageUrl }}" />
@endsection

@section('content')
    <div class="py-4">
        @php
            $breadcrumbs = [
                ['label' => $product->name, 'link' => route('products.show', $product->slug)],
                ['label' => 'Alternatives'],
            ];
        @endphp
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>

    <script type="application/ld+json">
        {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <div class="rounded-lg pb-10 pt-2 md:pb-14" style="background-color: var(--color-body-bg, #ffffff);">
        <header class="relative mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 py-7 shadow-sm sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary-400 via-primary-600 to-primary-400"></div>
            <div class="relative">
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-primary-100 bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">
                    <span>Alternatives guide</span>
                    <span class="h-1 w-1 rounded-full bg-primary-300"></span>
                    <span>{{ $year }} guide</span>
                </div>

                <h1 class="max-w-4xl text-3xl font-bold tracking-tight text-gray-950 sm:text-4xl">{{ $title }}</h1>
                <p class="mt-4 max-w-4xl text-base leading-7 text-gray-600">{{ $intro }}</p>

                <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium text-gray-600">
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5">{{ $alternatives->count() }} relevant options</span>
                    <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5">Editorial + relevance ranking</span>
                    <a href="#ranking-methodology" class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 hover:border-primary-200 hover:text-primary-700">Transparent methodology</a>
                </div>

                <div class="mt-7 flex items-center gap-4 border-t border-gray-100 pt-5">
                    <img src="{{ $product->logo_url }}" alt="{{ $product->name }} logo" class="h-12 w-12 flex-shrink-0 rounded-xl border border-gray-200 bg-white object-cover shadow-sm">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Alternatives to</p>
                        <a href="{{ route('products.show', $product->slug) }}" wire:navigate.hover class="font-bold text-gray-900 hover:text-primary-600">{{ $product->name }}</a>
                        <p class="mt-0.5 truncate text-sm text-gray-500">{{ $product->tagline }}</p>
                    </div>
                </div>
            </div>
        </header>

        @if($alternatives->isEmpty())
            <p class="text-gray-500">No alternatives found yet. Check back as new products are added.</p>
        @else
            <div class="mb-10 flex gap-4 rounded-2xl border border-primary-100 bg-primary-50/70 p-5 sm:p-6">
                <div class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">1</div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-primary-800">Quick answer</p>
                    <p class="mt-2 text-sm leading-6 text-gray-800">
                    The best {{ $product->name }} alternatives on this page start with
                    @foreach($alternatives->take(3) as $alt)
                        <a href="{{ route('products.show', ['product' => $alt->slug]) }}" wire:navigate.hover class="font-semibold text-blue-900 underline decoration-blue-300 underline-offset-2">{{ $alt->name }}</a>@if(!$loop->last), @endif
                    @endforeach.
                    Compare them side by side below to find the best fit for your audience, pricing needs, and workflow.
                    </p>
                </div>
            </div>

            <section id="decision-guide" class="mb-12 scroll-mt-24">
                <div class="mb-3">
                    <h2 class="text-xl font-semibold text-gray-900">Editorial Snapshot</h2>
                    <p class="mt-1 text-sm text-gray-500">A quick framing for what to compare before you start clicking through every option.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Why Readers Switch</p>
                        <p class="mt-2 text-sm leading-6 text-gray-700">
                            {{ $productEditorial['limitations'][0] ?? "Most people look for alternatives when they need a better pricing fit, a better workflow fit, or stronger support for their specific use case." }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Where {{ $product->name }} Still Fits</p>
                        @if(!empty($productEditorial['ideal_for']))
                            <ul class="mt-2 space-y-2 text-sm leading-6 text-gray-700">
                                @foreach(array_slice($productEditorial['ideal_for'], 0, 3) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-2 text-sm leading-6 text-gray-700">If {{ $product->name }} already matches your workflow, audience, and pricing comfort zone, a switch may not be worth the friction.</p>
                        @endif
                    </article>

                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">What To Compare First</p>
                        <ul class="mt-2 space-y-2 text-sm leading-6 text-gray-700">
                            <li>Feature depth for the exact workflow you care about</li>
                            <li>Pricing posture and whether free, freemium, or subscription fit your budget</li>
                            <li>Tradeoffs like setup friction, missing features, or team fit</li>
                        </ul>
                    </article>
                </div>
            </section>

            <section id="comparison-table" class="mb-12 scroll-mt-24">
                <div class="mb-3">
                    <h2 class="text-xl font-semibold text-gray-900">Top Alternatives At A Glance</h2>
                    <p class="mt-1 text-sm text-gray-500">A quick shortlist for readers who want editorial-style context before diving into the full ranked list.</p>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 bg-white">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="px-4 py-3">Alternative</th>
                                <th class="px-4 py-3">Best For</th>
                                <th class="px-4 py-3">Pricing</th>
                                <th class="px-4 py-3">Why Consider It</th>
                                <th class="px-4 py-3">Compare</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($topAlternatives as $alt)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="flex items-start gap-3">
                                            <img src="{{ $alt->logo_url }}" alt="{{ $alt->name }}" class="h-10 w-10 rounded-lg border border-gray-100 object-cover">
                                            <div>
                                                <a href="{{ route('products.show', ['product' => $alt->slug]) }}" wire:navigate.hover class="text-sm font-semibold text-gray-900 hover:text-primary-600">{{ $alt->name }}</a>
                                                <p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($alt->tagline, 80) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-600">{{ $alt->best_for_label ?: ($alt->primary_category_label ?: 'General use') }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600">{{ $alt->pricing_label }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($alt->decision_summary ?: $alt->match_summary, 120) }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        <a href="{{ route('pseo.compare', ['params' => $product->slug . '-vs-' . $alt->slug]) }}" class="font-medium text-primary-600 hover:underline">
                                            {{ $product->name }} vs {{ $alt->name }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="ranked-alternatives" class="mb-12 scroll-mt-24">
                <div class="mb-3">
                    <h2 class="text-xl font-semibold text-gray-900">Full Ranked List</h2>
                    <p class="mt-1 text-sm text-gray-500">Each recommendation includes the main reason it is a relevant alternative to {{ $product->name }}.</p>
                </div>

                <div class="space-y-4">
                    @foreach($alternatives as $index => $alt)
                        @php
                            $primaryCategory = $alt->categories->first(function ($category) {
                                $typeNames = $category->types->pluck('name')->map(fn($name) => strtolower((string) $name));

                                if ($typeNames->isEmpty()) {
                                    return true;
                                }

                                if ($typeNames->contains('pricing') || $typeNames->contains('best for')) {
                                    return false;
                                }

                                return $typeNames->contains('software')
                                    || $typeNames->contains('software categories')
                                    || $typeNames->contains('category');
                            });
                            $pricingCategory = $alt->categories->first(fn($category) => $category->types->contains('name', 'Pricing'));
                            $metaLinks = collect([
                                $primaryCategory ? [
                                    'label' => $alt->primary_category_label,
                                    'href' => route('categories.show', ['category' => $primaryCategory->slug]),
                                    'navigate' => true,
                                    'class' => 'text-xs hover:text-gray-800 hover:underline',
                                ] : null,
                                ($pricingCategory && $alt->pricing_label !== 'Pricing not listed') ? [
                                    'label' => $alt->pricing_label,
                                    'href' => route('pseo.pricing', ['pricing' => $pricingCategory->slug]),
                                    'navigate' => true,
                                    'class' => 'text-xs hover:text-gray-800 hover:underline',
                                ] : null,
                                [
                                    'label' => ($alt->match_source ?? null) === 'manual' ? 'Curated pick' : 'Relevance match',
                                    'href' => '#ranking-methodology',
                                    'navigate' => false,
                                    'class' => 'text-xs hover:text-gray-800 hover:underline',
                                ],
                            ])->filter()->values();
                        @endphp
                        <article id="alternative-{{ $alt->slug }}" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-md">
                            <div class="border-b border-gray-100 bg-gray-50/70 px-5 py-4">
                                <div class="flex items-start gap-4">
                                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-900 text-sm font-bold text-white">{{ $index + 1 }}</span>
                                <div class="flex-shrink-0">
                                    <img src="{{ $alt->logo_url }}" alt="{{ $alt->name }} logo" class="h-12 w-12 rounded-xl border border-gray-200 bg-white object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <a href="{{ route('products.show', ['product' => $alt->slug]) }}" wire:navigate.hover class="text-base font-semibold text-gray-900 hover:text-primary-600">
                                                    {{ $alt->name }}
                                                </a>
                                                <span class="rounded-full border border-primary-100 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700">{{ $alt->relevance_label }}</span>
                                            </div>
                                            <p class="mt-0.5 text-sm text-gray-500">{{ $alt->tagline }}</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                            @foreach($metaLinks as $metaLink)
                                                @if(!$loop->first)
                                                    <span class="text-gray-400">•</span>
                                                @endif
                                                @if($metaLink['href'])
                                                    <a
                                                        href="{{ $metaLink['href'] }}"
                                                        @if($metaLink['navigate']) wire:navigate.hover @endif
                                                        @click.stop
                                                        class="{{ $metaLink['class'] }}"
                                                    >
                                                        {{ $metaLink['label'] }}
                                                    </a>
                                                @else
                                                    <span class="{{ $metaLink['class'] }}">
                                                        {{ $metaLink['label'] }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            </div>
                            </div>

                            <div class="p-5 sm:p-6">
                                    <p class="text-sm font-medium leading-6 text-gray-800">{{ $alt->decision_summary ?: $alt->match_summary }}</p>

                                    @if($alt->editorial_take && $alt->editorial_take !== $alt->decision_summary)
                                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ $alt->editorial_take }}</p>
                                    @endif

                                    <div class="mt-5 grid gap-3 md:grid-cols-3">
                                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Better For</p>
                                            <p class="mt-2 text-sm leading-5 text-gray-700">
                                                {{ $alt->better_for_text ?: 'Readers who want a similar tool with a slightly different workflow fit.' }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Feature Highlights</p>
                                            @if(!empty($alt->feature_highlights))
                                                <ul class="mt-2 space-y-1.5 text-sm leading-5 text-gray-700">
                                                    @foreach($alt->feature_highlights as $feature)
                                                        <li>{{ $feature }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="mt-2 text-sm leading-5 text-gray-700">{{ $alt->primary_category_label ?: 'Similar product' }} option with overlapping category fit.</p>
                                            @endif
                                        </div>

                                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Watch Out If</p>
                                            <p class="mt-2 text-sm leading-5 text-gray-700">
                                                {{ $alt->watch_out_text ?: 'You need a near-identical replacement with no workflow changes.' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if(!empty($alt->match_summary) && $alt->match_summary !== $alt->decision_summary)
                                        <p class="mt-2 text-xs text-gray-500">Why it matches: {{ $alt->match_summary }}</p>
                                    @endif

                                    @if($alt->best_for_label)
                                        <p class="mt-2 text-xs text-gray-500">Best for: {{ $alt->best_for_label }}</p>
                                    @endif

                                    @if(!empty($alt->pros_points))
                                        <p class="mt-2 text-xs text-gray-500">Pros: {{ implode(' · ', $alt->pros_points) }}</p>
                                    @endif

                                    @if(!empty($alt->limitations_points))
                                        <p class="mt-2 text-xs text-gray-500">Tradeoffs: {{ implode(' · ', $alt->limitations_points) }}</p>
                                    @endif

                                    <div class="mt-5 flex flex-wrap gap-3 text-sm font-semibold">
                                        <a href="{{ route('products.show', ['product' => $alt->slug]) }}" wire:navigate.hover class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-white hover:bg-gray-700">
                                            View {{ $alt->name }}
                                        </a>
                                        <a href="{{ route('pseo.compare', ['params' => $product->slug . '-vs-' . $alt->slug]) }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-gray-700 hover:border-primary-200 hover:text-primary-700">
                                            Compare {{ $product->name }} vs {{ $alt->name }}
                                        </a>
                                    </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="ranking-methodology" class="mb-12 scroll-mt-24 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                <h2 class="text-xl font-semibold text-gray-900">How We Ranked These Alternatives</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600">
                    We prioritize alternatives that overlap with {{ $product->name }} in specific software category, use case, audience, pricing model, and product positioning.
                    Strong editorial matches can also be manually curated when there is a clear like-for-like replacement readers should evaluate. Where available, we also use structured product details like feature highlights, ideal users, use cases, and tradeoffs to make the recommendations more useful.
                    Votes and page views do not affect this ranking.
                </p>
            </section>

            @if(!empty($faqItems))
                <section id="faq" class="scroll-mt-24">
                    <div class="mb-3">
                        <h2 class="text-xl font-semibold text-gray-900">Frequently Asked Questions</h2>
                        <p class="mt-1 text-sm text-gray-500">Short answers for common questions about switching from {{ $product->name }}.</p>
                    </div>

                    <div class="space-y-3">
                        @foreach($faqItems as $item)
                            <details class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-gray-900">
                                    {{ $item['question'] }}
                                    <span class="text-lg text-gray-400 transition group-open:rotate-45">+</span>
                                </summary>
                                <p class="mt-3 text-sm leading-6 text-gray-600">{{ $item['answer'] }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
@endsection

@section('right_sidebar_content')
    <div class="sticky top-24 hidden space-y-4 md:block">
        <nav aria-label="On this page" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">On this page</h2>
            <ol class="mt-4 space-y-3 text-sm font-medium text-gray-600">
                <li><a href="#decision-guide" class="hover:text-primary-700">Decision guide</a></li>
                <li><a href="#comparison-table" class="hover:text-primary-700">At-a-glance comparison</a></li>
                <li><a href="#ranked-alternatives" class="hover:text-primary-700">Ranked alternatives</a></li>
                <li><a href="#ranking-methodology" class="hover:text-primary-700">Ranking methodology</a></li>
                @if(!empty($faqItems))
                    <li><a href="#faq" class="hover:text-primary-700">Frequently asked questions</a></li>
                @endif
            </ol>
        </nav>

        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
            <div class="flex items-center gap-3">
                <img src="{{ $product->logo_url }}" alt="" class="h-10 w-10 rounded-lg border border-gray-200 bg-white object-cover">
                <h3 class="text-sm font-semibold text-gray-800">About {{ $product->name }}</h3>
            </div>
            <p class="mt-3 text-xs leading-5 text-gray-500">
                {{ \Illuminate\Support\Str::limit(strip_tags((string) ($product->product_page_tagline ?: $product->tagline ?: $product->description)), 140) }}
            </p>
            <a href="{{ route('products.show', ['product' => $product->slug]) }}" wire:navigate.hover class="mt-4 inline-flex text-xs font-semibold text-primary-700 hover:underline">
                View product details &rarr;
            </a>
        </div>

        <div class="rounded-2xl border border-primary-100 bg-primary-50 p-5">
            <h3 class="text-sm font-semibold text-primary-900">How rankings work</h3>
            <p class="mt-2 text-xs leading-5 text-primary-800">Specific category, use case, audience, pricing, and product-positioning signals. Votes and page views are excluded.</p>
        </div>
    </div>
@endsection
