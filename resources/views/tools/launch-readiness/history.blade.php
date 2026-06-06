@extends('layouts.launch-readiness')

@section('title', 'Launch Readiness History - Software on the Web')
@section('og_title', 'Public launch-readiness scan history')
@section('meta_description', 'Browse saved launch-readiness scans from all users and guests.')

@section('content')
    @php
        $statusRingClasses = static function (int $score): string {
            return match (true) {
                $score >= 90 => 'border-emerald-500 bg-emerald-50 text-emerald-700',
                $score >= 75 => 'border-amber-400 bg-amber-50 text-amber-700',
                default => 'border-rose-400 bg-rose-50 text-rose-600',
            };
        };

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

    <div class="mx-auto max-w-7xl space-y-8">
        <section class="flex flex-col gap-5 pt-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Is your site ready for launch?</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    View all previously completed site checks.
                </p>
            </div>

            <form method="GET" action="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="w-full lg:max-w-xs">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <label for="q" class="sr-only">Search domains</label>
                <div class="flex h-11 items-center rounded-2xl border border-slate-200 bg-white px-3 shadow-sm shadow-slate-200/30">
                    <svg class="mr-2 h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.473 9.765l3.63 3.631a.75.75 0 1 0 1.06-1.06l-3.63-3.632A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                    </svg>
                    <input id="q" name="q" type="text" value="{{ $query }}" placeholder="Search domains..." class="h-full w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                </div>
            </form>
        </section>

        @unless($toolTablesReady ?? false)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                The tool database tables are not migrated yet, so public history is not available yet.
            </div>
        @endunless

        <section class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($history as $item)
                    @php
                        $host = parse_url($item->final_url ?: $item->submitted_url, PHP_URL_HOST) ?: parse_url($item->submitted_url, PHP_URL_HOST);
                        $favicon = $host ? 'https://www.google.com/s2/favicons?domain=' . $host . '&sz=64' : null;
                    @endphp
                    <a
                        href="{{ route('launch-readiness.results.show', ['toolSlug' => $toolSlug, 'toolScan' => $item]) }}"
                        class="group rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white">
                                        @if($favicon)
                                            <img src="{{ $favicon }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <span class="text-xs font-semibold text-slate-500">{{ strtoupper(substr(parse_url($item->submitted_url, PHP_URL_HOST) ?: 'S', 0, 1)) }}</span>
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[15px] font-semibold text-slate-900 group-hover:text-slate-950">{{ $item->final_url ?: $item->submitted_url }}</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ optional($item->scanned_at)->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>

                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-sm font-semibold {{ $statusRingClasses((int) $item->launch_score) }}">
                                {{ $item->launch_score }}
                            </span>
                        </div>

                        <div class="mt-6 grid grid-cols-3 gap-3 border-t border-slate-100 pt-4">
                            <div>
                                <p class="text-[11px] text-slate-400">Passed</p>
                                <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                                    <svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06L9.25 10.69 7.78 9.22a.75.75 0 1 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $item->passed_checks }}</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400">Warnings</p>
                                <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                                    <svg class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.486 0l4.58 8.145c.75 1.334-.213 2.993-1.742 2.993H5.419c-1.53 0-2.492-1.66-1.743-2.993l4.58-8.145ZM11 7a1 1 0 1 0-2 0v2a1 1 0 1 0 2 0V7Zm-1 6a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 10 13Z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $item->warning_checks }}</span>
                                </p>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-400">Errors</p>
                                <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                                    <svg class="h-4 w-4 shrink-0 text-rose-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.53-10.47a.75.75 0 0 0-1.06-1.06L10 8.94 7.53 6.47a.75.75 0 0 0-1.06 1.06L8.94 10l-2.47 2.47a.75.75 0 1 0 1.06 1.06L10 11.06l2.47 2.47a.75.75 0 0 0 1.06-1.06L11.06 10l2.47-2.47Z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $item->failed_checks }}</span>
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="sm:col-span-2 xl:col-span-3 rounded-[24px] border border-dashed border-slate-200 bg-white px-6 py-14 text-center shadow-sm shadow-slate-200/30">
                        <p class="text-sm font-medium text-slate-900">No saved scans found yet.</p>
                        <p class="mt-2 text-sm text-slate-500">Run a scan with “Save to history” enabled to populate the public feed.</p>
                    </div>
                @endforelse
            </div>

            @if($history->total() > 0)
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-xs text-slate-500">
                        Showing {{ $history->firstItem() ?? 0 }} to {{ $history->lastItem() ?? 0 }} of {{ $history->total() }} results
                    </p>

                    <div class="rounded-[24px] border border-slate-200 bg-white px-2.5 py-2 shadow-sm shadow-slate-200/40">
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

                            <form method="GET" action="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="flex flex-wrap items-center gap-2">
                                @if($query !== '')
                                    <input type="hidden" name="q" value="{{ $query }}">
                                @endif
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

                            <form method="GET" action="{{ route('launch-readiness.history', ['toolSlug' => $toolSlug]) }}" class="flex items-center gap-2">
                                @if($query !== '')
                                    <input type="hidden" name="q" value="{{ $query }}">
                                @endif
                                <input type="hidden" name="per_page" value="{{ $perPage }}">
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
