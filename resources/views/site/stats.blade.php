@extends('layouts.app', [
    'mainContentMaxWidth' => 'max-w-4xl',
    'containerMaxWidth' => 'max-w-[96rem]',
    'headerPadding' => 'px-4 md:px-0',
])

@section('title', 'Platform stats | '.config('app.name'))
@section('header-title', 'Platform stats')
@section('meta_description', 'Track software launches, website clicks, and AI crawler activity on Software on the Web. Explore live product counts and startup directory stats.')

@section('before_header_title')
    <x-breadcrumbs :items="[['label' => 'Stats']]" />
@endsection

@section('left_sidebar_content')
    @include('products.partials._browse')
@endsection

@section('content')
    @php
        $metrics = [
            ['Live products', $totals['public_products'], 'All time', route('products.catalog'), 'All products'],
            ['Launches this week', $weekly->last()['launches'], $week->format('M j').'–'.$week->copy()->addDays(6)->format('M j'), route('products.weekly.redirect'), 'Weekly board'],
            ['Website clicks', $totals['product_clicks'], 'All time', null, 'Outbound product clicks'],
            ['AI crawler requests', $totals['total_ai_reads'] === null ? null : $totals['ai_bot_requests'], 'Last 30 days', null, 'Crawler logs'],
        ];
        $activityMetrics = ['launches' => 'Launches', 'clicks' => 'Website clicks'];
        if ($totals['total_ai_reads'] !== null) {
            $activityMetrics['ai_requests'] = 'AI requests';
        }
        $activityPeaks = collect(array_keys($activityMetrics))->mapWithKeys(fn ($key) => [$key => max(1, $weekly->max($key))]);
        $previousWeekUrl = route('products.byWeek', ['year' => $previousWeek->isoWeekYear(), 'week' => $previousWeek->isoWeek()]);
    @endphp

    <div class="space-y-5 pb-8">
        <div class="space-y-2">
            <div class="md:hidden">
                <x-breadcrumbs :items="[['label' => 'Stats']]" />
            </div>
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <p class="text-sm text-gray-600">Launches, product discovery, and directory coverage.</p>
                <p class="text-xs text-gray-500">Updated <time datetime="{{ $updatedAt->toIso8601String() }}">{{ $updatedAt->format('M j, Y, H:i T') }}</time></p>
            </div>
        </div>

        <dl class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ($metrics as [$label, $value, $period])
                <div @class(['relative rounded-xl border bg-white p-4', 'border-primary-500' => $loop->first, 'border-gray-200' => !$loop->first])>
                    <dt class="text-sm font-medium text-gray-700">
                        @if ($loop->first)
                            <a href="{{ route('products.catalog') }}" class="block hover:underline after:absolute after:inset-0 after:rounded-xl">{{ $label }} <span aria-hidden="true">↗</span></a>
                        @else
                            {{ $label }}
                        @endif
                    </dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ $value === null ? 'Unavailable' : number_format($value) }}</dd>
                    <dd class="mt-1 text-xs text-gray-600">{{ $period }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="rounded-xl border border-gray-200 bg-white p-4" x-data="{ metric: 'launches' }">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-gray-900">Weekly activity</h2>
                <label class="text-sm text-gray-600">
                    <span class="sr-only">Activity metric</span>
                    <select x-model="metric" class="rounded-lg border-gray-200 text-sm">
                        @foreach ($activityMetrics as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            @foreach ($activityMetrics as $key => $label)
                <div x-show="metric === '{{ $key }}'" @if (!$loop->first) x-cloak @endif class="mt-3">
                    <div class="flex h-24 items-end gap-1 border-b border-gray-200 sm:gap-2" role="img" aria-label="{{ $label }} by week. Exact values below.">
                        @foreach ($weekly as $weekStats)
                            <div class="flex h-full min-w-0 flex-1 items-end" title="{{ $weekStats['label'] }}: {{ number_format($weekStats[$key]) }}">
                                <div class="w-full rounded-t bg-primary-500" style="height: {{ 100 * $weekStats[$key] / $activityPeaks[$key] }}%"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1 flex justify-between text-xs text-gray-500">
                        <span>{{ $weekly->first()['label'] }}</span>
                        <span>{{ $weekly->last()['label'] }} · Week starts</span>
                    </div>
                    <details class="mt-3 text-sm text-gray-600">
                        <summary class="cursor-pointer">Weekly values</summary>
                        <dl class="mt-2 grid grid-cols-2 gap-x-4 sm:grid-cols-3">
                            @foreach ($weekly as $weekStats)
                                <div class="flex justify-between gap-2 border-b border-gray-100 py-1">
                                    <dt>{{ $weekStats['label'] }}</dt>
                                    <dd class="tabular-nums">{{ number_format($weekStats[$key]) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </details>
                </div>
            @endforeach
        </section>

        <div class="grid gap-4 sm:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-4">
                <h2 class="font-semibold text-gray-900">Last week’s top organic products</h2>
                <a href="{{ $previousWeekUrl }}" class="text-xs text-primary-600 hover:underline">{{ $previousWeek->format('M j') }}–{{ $previousWeek->copy()->addDays(6)->format('M j') }} weekly board</a>
                @if ($leaders->isEmpty())
                    <p class="mt-3 text-sm text-gray-600">No ranked products for this week.</p>
                @else
                    <ol class="mt-2 list-decimal space-y-2 pl-5 text-sm">
                        @foreach ($leaders as $product)
                            <li><a href="{{ route('products.show', $product->slug) }}" class="text-primary-600 hover:underline">{{ $product->name }}</a></li>
                        @endforeach
                    </ol>
                @endif
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-4">
                <h2 class="font-semibold text-gray-900"><a href="{{ route('custom-pages.show', 'directories') }}" class="hover:underline">Startup directories</a></h2>
                <dl class="mt-2 divide-y divide-gray-100 text-sm">
                    @foreach ($directories as $label => $value)
                        <div class="flex justify-between gap-2 py-2">
                            <dt>{{ $label }}</dt>
                            <dd class="font-medium tabular-nums">{{ $value === null ? 'Unavailable' : number_format($value, $value == floor($value) ? 0 : 1) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="font-semibold text-gray-900">Catalog breakdown</h2>
            <div class="mt-2 grid gap-4 sm:grid-cols-2">
                @foreach (['Categories' => $categories, 'Tech stacks' => $techStacks] as $label => $items)
                    <div>
                        <h3 class="text-sm font-medium text-gray-600">{{ $label }}</h3>
                        <dl class="divide-y divide-gray-100 text-sm">
                            @forelse ($items as $item)
                                <div class="flex justify-between gap-2 py-2">
                                    <dt><a href="{{ $item instanceof \App\Models\Category ? $item->publicUrl() : route('pseo.builtWith', $item->slug) }}" class="text-primary-600 hover:underline">{{ $item->name }}</a></dt>
                                    <dd class="font-medium tabular-nums">{{ number_format($item->products_count) }}</dd>
                                </div>
                            @empty
                                <div class="py-2 text-gray-600"><dt>Live products</dt><dd>None</dd></div>
                            @endforelse
                        </dl>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4">
            <h2 class="font-semibold text-gray-900">Stats snapshot</h2>
            <table class="mt-2 w-full text-left text-xs sm:text-sm">
                <thead class="border-b border-gray-200 text-gray-600">
                    <tr><th scope="col" class="py-2">Metric</th><th scope="col">Value</th><th scope="col">Period</th><th scope="col">Source</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($metrics as [$label, $value, $period, $url, $source])
                        <tr>
                            <th scope="row" class="py-2 pr-2 font-medium">{{ $label }}</th>
                            <td class="pr-2 tabular-nums">{{ $value === null ? 'Unavailable' : number_format($value) }}</td>
                            <td class="pr-2">{{ $period }}</td>
                            <td>
                                @if ($url)
                                    <a href="{{ $url }}" class="text-primary-600 hover:underline">{{ $source }}</a>
                                @else
                                    {{ $source }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <th scope="row" class="py-2 pr-2 font-medium">Listed directories</th>
                        <td class="pr-2 tabular-nums">{{ number_format($directories['Listed directories']) }}</td>
                        <td class="pr-2">Current</td>
                        <td><a href="{{ route('custom-pages.show', 'directories') }}" class="text-primary-600 hover:underline">Directories</a></td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
@endsection
