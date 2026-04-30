@php
    $mainContentMaxWidth = 'max-w-7xl';
    $pageUrl = route('pseo.alternatives', ['product' => $product->slug]);
    $topAlternatives = $alternatives->take(5)->values();
    $productSummary = trim(strip_tags((string) ($product->product_page_tagline ?: $product->tagline ?: $product->description)));

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

    <div class="rounded-lg py-6 md:py-8" style="background-color: var(--color-body-bg, #ffffff);">
        <div class="mb-6 rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-5">
            <div class="flex items-start gap-4">
                <img src="{{ $product->logo_url }}" alt="{{ $product->name }}" class="h-14 w-14 flex-shrink-0 rounded-xl border border-gray-100 object-cover">
                <div class="min-w-0">
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-gray-400">You are comparing alternatives to</p>
                    <a href="{{ route('products.show', $product->slug) }}" class="text-lg font-bold text-gray-900 hover:text-primary-600">{{ $product->name }}</a>
                    <p class="mt-1 text-sm text-gray-500">{{ $product->tagline }}</p>
                    @if($productSummary !== '')
                        <p class="mt-2 max-w-3xl text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($productSummary, 200) }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-8">
            <h1 class="mb-2 text-2xl font-bold text-gray-900 md:text-3xl">{{ $title }}</h1>
            <p class="mb-3 max-w-4xl text-sm leading-6 text-gray-600">{{ $intro }}</p>
            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                <span>{{ $alternatives->count() }} alternatives found</span>
                <span class="hidden h-1 w-1 rounded-full bg-gray-300 md:inline-block"></span>
                <span>Includes curated picks and algorithmic matches</span>
                <span class="hidden h-1 w-1 rounded-full bg-gray-300 md:inline-block"></span>
                <span>Ranked by category fit, audience overlap, pricing overlap, technical overlap, and product positioning</span>
            </div>
        </div>

        @if($alternatives->isEmpty())
            <p class="text-gray-500">No alternatives found yet. Check back as new products are added.</p>
        @else
            <div class="mb-8 rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Quick Answer</p>
                <p class="mt-2 text-sm leading-6 text-blue-900">
                    The best {{ $product->name }} alternatives on this page start with
                    @foreach($alternatives->take(3) as $alt)
                        <a href="{{ route('products.show', ['product' => $alt->slug]) }}" class="font-semibold text-blue-900 underline decoration-blue-300 underline-offset-2">{{ $alt->name }}</a>@if(!$loop->last), @endif
                    @endforeach.
                    Compare them side by side below to find the best fit for your audience, pricing needs, and workflow.
                </p>
            </div>

            <section class="mb-10">
                <div class="mb-3">
                    <h2 class="text-xl font-semibold text-gray-900">Editorial Snapshot</h2>
                    <p class="mt-1 text-sm text-gray-500">A quick framing for what to compare before you start clicking through every option.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <article class="rounded-2xl border border-gray-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Why Readers Switch</p>
                        <p class="mt-2 text-sm leading-6 text-gray-700">
                            {{ $productEditorial['limitations'][0] ?? "Most people look for alternatives when they need a better pricing fit, a better workflow fit, or stronger support for their specific use case." }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-gray-200 bg-white p-4">
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

                    <article class="rounded-2xl border border-gray-200 bg-white p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">What To Compare First</p>
                        <ul class="mt-2 space-y-2 text-sm leading-6 text-gray-700">
                            <li>Feature depth for the exact workflow you care about</li>
                            <li>Pricing posture and whether free, freemium, or subscription fit your budget</li>
                            <li>Tradeoffs like setup friction, missing features, or team fit</li>
                        </ul>
                    </article>
                </div>
            </section>

            <section class="mb-10">
                <div class="mb-3">
                    <h2 class="text-xl font-semibold text-gray-900">Top Alternatives At A Glance</h2>
                    <p class="mt-1 text-sm text-gray-500">A quick shortlist for readers who want editorial-style context before diving into the full ranked list.</p>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-gray-200">
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
                                                <a href="{{ route('products.show', ['product' => $alt->slug]) }}" class="text-sm font-semibold text-gray-900 hover:text-primary-600">{{ $alt->name }}</a>
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

            <section class="mb-10">
                <div class="mb-3">
                    <h2 class="text-xl font-semibold text-gray-900">Full Ranked List</h2>
                    <p class="mt-1 text-sm text-gray-500">Each recommendation includes the main reason it is a relevant alternative to {{ $product->name }}.</p>
                </div>

                <div class="space-y-4">
                    @foreach($alternatives as $index => $alt)
                        <article class="rounded-2xl border border-gray-100 p-4 transition-all hover:border-gray-200 hover:shadow-sm">
                            <div class="flex items-start gap-4">
                                <span class="mt-1 w-5 flex-shrink-0 text-sm font-bold text-gray-300">{{ $index + 1 }}</span>
                                <div class="flex-shrink-0">
                                    <img src="{{ $alt->logo_url }}" alt="{{ $alt->name }}" class="h-12 w-12 rounded-xl border border-gray-100 object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0">
                                            <a href="{{ route('products.show', ['product' => $alt->slug]) }}" class="text-base font-semibold text-gray-900 hover:text-primary-600">
                                                {{ $alt->name }}
                                            </a>
                                            <p class="mt-0.5 text-sm text-gray-500">{{ $alt->tagline }}</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 text-xs">
                                            @if($alt->primary_category_label)
                                                <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-700">{{ $alt->primary_category_label }}</span>
                                            @endif
                                            <span class="rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-green-700">{{ $alt->pricing_label }}</span>
                                            <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-blue-700">
                                                {{ ($alt->match_source ?? null) === 'manual' ? 'Curated pick' : 'Algorithmic match' }}
                                            </span>
                                            <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-amber-700">{{ (int) $alt->votes_count }} votes</span>
                                        </div>
                                    </div>

                                    <p class="mt-3 text-sm leading-6 text-gray-700">{{ $alt->decision_summary ?: $alt->match_summary }}</p>

                                    @if($alt->editorial_take && $alt->editorial_take !== $alt->decision_summary)
                                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ $alt->editorial_take }}</p>
                                    @endif

                                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Better For</p>
                                            <p class="mt-2 text-sm leading-5 text-gray-700">
                                                {{ $alt->better_for_text ?: 'Readers who want a similar tool with a slightly different workflow fit.' }}
                                            </p>
                                        </div>

                                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
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

                                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
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

                                    <div class="mt-3 flex flex-wrap gap-4 text-xs font-medium">
                                        <a href="{{ route('products.show', ['product' => $alt->slug]) }}" class="text-primary-600 hover:underline">
                                            View {{ $alt->name }}
                                        </a>
                                        <a href="{{ route('pseo.compare', ['params' => $product->slug . '-vs-' . $alt->slug]) }}" class="text-primary-600 hover:underline">
                                            Compare {{ $product->name }} vs {{ $alt->name }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mb-10 rounded-2xl border border-gray-200 bg-gray-50 p-5">
                <h2 class="text-xl font-semibold text-gray-900">How We Ranked These Alternatives</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600">
                    We prioritize alternatives that overlap with {{ $product->name }} in software category, audience, pricing model, technical profile, and product positioning.
                    Strong editorial matches can also be manually curated when there is a clear like-for-like replacement readers should evaluate. Where available, we also use structured product details like feature highlights, ideal users, use cases, and tradeoffs to make the recommendations more useful.
                </p>
            </section>

            @if(!empty($faqItems))
                <section>
                    <div class="mb-3">
                        <h2 class="text-xl font-semibold text-gray-900">Frequently Asked Questions</h2>
                        <p class="mt-1 text-sm text-gray-500">Short answers for common questions about switching from {{ $product->name }}.</p>
                    </div>

                    <div class="space-y-3">
                        @foreach($faqItems as $item)
                            <article class="rounded-2xl border border-gray-200 bg-white p-4">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $item['question'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-gray-600">{{ $item['answer'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </div>
@endsection

@section('right_sidebar_content')
    <div class="hidden space-y-4 md:block">
        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">About {{ $product->name }}</h3>
            <p class="text-xs leading-5 text-gray-500">
                {{ \Illuminate\Support\Str::limit(strip_tags((string) ($product->product_page_tagline ?: $product->tagline ?: $product->description)), 140) }}
            </p>
            <a href="{{ route('products.show', ['product' => $product->slug]) }}" class="mt-3 block text-xs text-primary-600 hover:underline">
                View {{ $product->name }} &rarr;
            </a>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white p-4">
            <h3 class="mb-2 text-sm font-semibold text-gray-700">Use This Page To</h3>
            <p class="text-xs leading-5 text-gray-500">Shortlist like-for-like options, compare pricing posture, and find better fits for your specific audience or workflow.</p>
        </div>
    </div>
@endsection
