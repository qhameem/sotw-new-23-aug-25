@extends('layouts.launch-readiness')

@section('title', $toolPublicScansSeo['title_tag'])
@section('meta_description', $toolPublicScansSeo['meta_description'])

@section('content')
    @php
        $lastPage = $history->lastPage();
        $currentPage = $history->currentPage();
        $paginationPages = collect();

        if ($lastPage > 0) {
            $rawPages = collect([1, 2, 3, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage - 2, $lastPage - 1, $lastPage])
                ->filter(fn (int $page) => $page >= 1 && $page <= $lastPage)
                ->unique()
                ->sort()
                ->values();

            $previous = null;
            $paginationPages = $rawPages->flatMap(function (int $page) use (&$previous) {
                $items = [];

                if ($previous !== null && $page - $previous > 1) {
                    $items[] = 'ellipsis';
                }

                $items[] = $page;
                $previous = $page;

                return $items;
            })->values();
        }
    @endphp

    @php
        $listView = $viewMode === 'list';
        $state = array_filter([
            'q' => $query,
            'sort' => $sort === 'score' ? $sort : null,
            'filter' => $filter === 'all' ? null : $filter,
            'view' => $listView ? $viewMode : null,
            'per_page' => $perPage === $allowedPerPage[0] ? null : $perPage,
        ], fn ($value) => $value !== null && $value !== '');
        $historyPath = route('launch-readiness.history', ['toolSlug' => $toolSlug]);
        $scansUrl = fn (array $changes = []) => route('launch-readiness.history', [
            'toolSlug' => $toolSlug,
            ...array_replace($state, $changes),
        ]);
        $listColumns = 'lg:grid-cols-[minmax(0,1.55fr)_3.25rem_minmax(0,1.55fr)_7rem_minmax(0,11rem)]';
        $selectedControl = 'bg-[var(--lr-brand)] text-[color:var(--lr-button-label)] shadow-[0_3px_0_var(--lr-brand-edge)]';
        $idleControl = 'text-neutral-700 hover:bg-neutral-100';
    @endphp

    <div class="space-y-7">
        <div>
            <nav aria-label="Breadcrumb" class="mb-5 text-xs text-neutral-600">
                <ol class="flex flex-wrap items-center gap-x-1 gap-y-1">
                    <li>
                        <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}"
                            class="underline underline-offset-4 hover:text-neutral-950">Launch Readiness Checker</a>
                    </li>
                    <li class="flex items-center gap-1">
                        <span aria-hidden="true">/</span>
                        <span aria-current="page" class="font-medium text-neutral-800">Public Scans</span>
                    </li>
                </ol>
            </nav>
            <div class="grid gap-x-4 gap-y-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                <h1 class="text-4xl font-bold tracking-tight text-neutral-950 sm:text-[44px]">{{ $toolPublicScansSeo['h1'] }}</h1>
                <p class="text-base leading-6 text-neutral-600 sm:col-start-1 sm:row-start-2">Browse website scans shared by users and guests.</p>
                <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}"
                    class="inline-flex min-h-11 w-fit items-center rounded-lg px-5 py-2.5 text-sm font-semibold hover:brightness-95 sm:col-start-2 sm:row-start-1 {{ $selectedControl }}">
                    Scan your site
                </a>
            </div>
        </div>

        <div class="space-y-3">
            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-3 sm:flex">
                <nav aria-label="Sort scans" class="flex w-fit items-center gap-0.5 rounded-xl border border-neutral-200 bg-white p-1">
                    @foreach(['latest' => 'Latest', 'score' => 'Highest score'] as $option => $label)
                        <a href="{{ $scansUrl(['sort' => $option === 'latest' ? null : $option]) }}"
                            @if($sort === $option) aria-current="true" @endif
                            class="inline-flex min-h-9 items-center whitespace-nowrap rounded-lg px-2.5 text-sm font-semibold sm:px-3.5 {{ $sort === $option ? $selectedControl : $idleControl }}">{{ $label }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ $historyPath }}" role="search"
                    class="order-last col-span-2 flex min-h-12 min-w-0 flex-1 items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 focus-within:ring-2 focus-within:ring-neutral-400 sm:order-none">
                    @foreach(\Illuminate\Support\Arr::except($state, 'q') as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit" class="inline-flex h-9 w-7 shrink-0 items-center justify-center text-neutral-600" aria-label="Search scans">
                        @svg('carbon-search', 'h-5 w-5')
                    </button>
                    <label for="q" class="sr-only">Search domains</label>
                    <input id="q" name="q" type="search" value="{{ $query }}" placeholder="Search domains..."
                        class="w-full min-w-0 border-0 bg-transparent p-0 text-base text-neutral-950 focus:outline-none focus:ring-0">
                </form>

                <nav aria-label="Scan layout" class="flex items-center gap-0.5 rounded-xl border border-neutral-200 bg-white p-1">
                    @foreach(['grid' => 'Grid view', 'list' => 'List view'] as $option => $label)
                        <a href="{{ $scansUrl(['view' => $option === 'grid' ? null : $option, 'page' => $currentPage > 1 ? $currentPage : null]) }}"
                            aria-label="{{ $label }}" title="{{ $label }}"
                            @if($viewMode === $option) aria-current="true" @endif
                            class="inline-flex h-9 w-10 items-center justify-center rounded-lg sm:w-11 {{ $viewMode === $option ? $selectedControl : $idleControl }}">
                            @if($option === 'grid')
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <rect x="4" y="4" width="6" height="6" rx="1" /><rect x="14" y="4" width="6" height="6" rx="1" />
                                    <rect x="4" y="14" width="6" height="6" rx="1" /><rect x="14" y="14" width="6" height="6" rx="1" />
                                </svg>
                            @else
                                @svg('carbon-list', 'h-5 w-5')
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>

            <nav aria-label="Filter scans" class="flex flex-wrap gap-2">
                @foreach($filters as $option => $label)
                    <a href="{{ $scansUrl(['filter' => $option === 'all' ? null : $option]) }}"
                        @if($filter === $option) aria-current="true" @endif
                        class="inline-flex min-h-8 items-center rounded-md border px-2.5 text-xs font-medium transition-colors {{ $filter === $option ? 'border-neutral-900 bg-neutral-900 text-white' : 'border-neutral-300 bg-transparent text-neutral-700 hover:border-neutral-400 hover:bg-neutral-100' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        @unless($toolTablesReady ?? false)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                The tool database tables are not migrated yet, so public scans are unavailable.
            </div>
        @endunless

        <section aria-label="Scan results" class="space-y-6">
            @if($history->isEmpty())
                <div class="rounded-2xl border border-neutral-200 bg-white px-6 py-14 text-center">
                    <p class="font-semibold text-neutral-950">No scans found.</p>
                    <p class="mt-2 text-sm text-neutral-600">
                        {{ $query !== '' || $filter !== 'all' ? 'Try a different search or filter.' : 'Run a scan with “Save to public history” enabled to share it here.' }}
                    </p>
                </div>
            @else
                <div @class(['overflow-hidden rounded-2xl border border-[#e1e1db] bg-white' => $listView])>
                    @if($listView)
                        <div aria-hidden="true" class="hidden items-center gap-4 bg-[#f2f2ee] px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-neutral-600 lg:grid {{ $listColumns }}">
                            <span class="pl-14">Website</span><span>Score</span><span>Results</span><span>Scanned</span><span></span>
                        </div>
                    @endif
                    <ul role="list" @class([
                        'divide-y divide-[#eaeae5]' => $listView,
                        'grid gap-5 sm:grid-cols-2 xl:grid-cols-3' => ! $listView,
                    ])>
                        @foreach($history as $item)
                            @include('tools.launch-readiness.partials.public-scan')
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($history->total() > 0)
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-xs text-slate-500">
                        Showing {{ $history->firstItem() ?? 0 }} to {{ $history->lastItem() ?? 0 }} of {{ $history->total() }} results
                    </p>

                    <div class="rounded-xl border border-slate-200 bg-white px-2.5 py-2 shadow-sm shadow-slate-200/40">
                        <div class="flex flex-col gap-2.5 md:flex-row md:flex-wrap md:items-center md:justify-end">
                            <div class="flex items-center gap-1">
                                <a
                                    href="{{ $history->onFirstPage() ? '#' : $history->previousPageUrl() }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-transparent text-slate-700 transition {{ $history->onFirstPage() ? 'pointer-events-none opacity-40' : 'hover:border-slate-200 hover:bg-slate-50' }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M11.78 15.53a.75.75 0 0 1-1.06 0l-5-5a.75.75 0 0 1 0-1.06l5-5a.75.75 0 0 1 1.06 1.06L7.31 10l4.47 4.47a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </a>

                                @foreach($paginationPages as $page)
                                    @if($page === 'ellipsis')
                                        <span class="px-1 text-sm text-slate-400">...</span>
                                    @else
                                        <a
                                            href="{{ $history->url($page) }}"
                                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-full px-2.5 text-xs font-medium transition {{ $page === $currentPage ? 'border border-indigo-300 bg-indigo-100/70 text-indigo-700 shadow-sm' : 'text-slate-900 hover:bg-slate-50' }}"
                                        >
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endforeach

                                <a
                                    href="{{ $history->hasMorePages() ? $history->nextPageUrl() : '#' }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-transparent text-slate-700 transition {{ $history->hasMorePages() ? 'hover:border-slate-200 hover:bg-slate-50' : 'pointer-events-none opacity-40' }}"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.22 4.47a.75.75 0 0 1 1.06 0l5 5a.75.75 0 0 1 0 1.06l-5 5a.75.75 0 0 1-1.06-1.06L12.69 10 8.22 5.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </div>

                            <form method="GET" action="{{ $historyPath }}" class="flex flex-wrap items-center gap-2">
                                @foreach(\Illuminate\Support\Arr::except($state, 'per_page') as $name => $value)
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endforeach
                                <label for="per_page" class="sr-only">Results per page</label>
                                <select
                                    id="per_page"
                                    name="per_page"
                                    onchange="this.form.submit()"
                                    class="h-8 rounded-full border border-indigo-300 bg-white pl-3 pr-9 text-xs font-medium text-slate-900 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-0"
                                >
                                    @foreach($allowedPerPage as $option)
                                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} / page</option>
                                    @endforeach
                                </select>
                            </form>

                            <form method="GET" action="{{ $historyPath }}" class="flex items-center gap-2">
                                @foreach($state as $name => $value)
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endforeach
                                <span class="text-xs font-medium text-slate-900">Go to</span>
                                <label for="goto_page" class="sr-only">Go to page</label>
                                <input
                                    id="goto_page"
                                    name="page"
                                    type="number"
                                    min="1"
                                    max="{{ max(1, $lastPage) }}"
                                    inputmode="numeric"
                                    class="h-8 w-16 rounded-full border border-indigo-300 bg-white px-2 text-center text-xs font-medium text-slate-900 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-0"
                                >
                                <span class="text-xs font-medium text-slate-900">Page</span>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
