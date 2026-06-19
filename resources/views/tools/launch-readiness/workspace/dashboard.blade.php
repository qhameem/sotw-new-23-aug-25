@extends('layouts.launch-readiness-app')

@section('title', 'Dashboard - ' . $toolBrandingSiteName)
@section('meta_description', 'Review your recent launch-readiness tests and rerun or delete them from one dashboard.')

@section('content')
    <style>
        .launch-readiness-dashboard-table-body,
        .launch-readiness-dashboard-table-body * {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
        }
    </style>

    <div class="mx-auto max-w-7xl space-y-8" x-data="launchReadinessDashboard()">
        <section class="overflow-hidden rounded-xl border bg-white shadow-[0_18px_50px_-30px_rgba(15,23,42,0.25)]" style="border-color: var(--lr-border);">
            <div class="flex flex-col gap-4 border-b px-7 py-6 lg:flex-row lg:items-center lg:justify-between" style="border-color: var(--lr-border);">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-[var(--lr-text)]">Recent Tests</h1>
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-normal" style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text); font-size: 0.75rem; font-weight: 400;">Daily allowed: {{ $dailyAllowed }}</span>
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-normal" style="border-color: var(--lr-border); background: var(--lr-panel-strong); color: var(--lr-text); font-size: 0.75rem; font-weight: 400;">Used today: {{ $usedToday }}</span>
                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-normal" style="border-color: color-mix(in srgb, var(--lr-success) 35%, transparent); background: color-mix(in srgb, var(--lr-success) 14%, transparent); color: var(--lr-success); font-size: 0.75rem; font-weight: 400;">Remaining today: {{ $remainingToday }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('launch-readiness.dashboard.scans.clear', ['toolSlug' => $toolSlug]) }}">
                        @csrf
                        <button type="submit" class="inline-flex h-8 items-center justify-center rounded-xl border border-rose-300 px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Clear All</button>
                    </form>

                    <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}" class="inline-flex h-8 items-center justify-center rounded-xl border border-slate-300 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Run manual Test</a>
                </div>
            </div>

            <div class="border-b px-7 py-5" style="border-color: var(--lr-border);">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-sm text-[var(--lr-muted)]">
                        Showing {{ $scans->firstItem() ?? 0 }}-{{ $scans->lastItem() ?? 0 }} of {{ $scans->total() }} sites
                    </p>

                    <form x-ref="bulkForm" method="POST" action="{{ route('launch-readiness.dashboard.scans.bulk', ['toolSlug' => $toolSlug]) }}" class="flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="hidden" name="action" x-model="action">
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="selected[]" :value="id">
                        </template>
                        <button type="button" class="inline-flex h-8 items-center justify-center rounded-xl border border-slate-300 px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="selected.length === 0" @click="submitAction('retest')">Retest Selected</button>
                        <button type="button" class="inline-flex h-8 items-center justify-center rounded-xl border border-rose-300 px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="selected.length === 0" @click="submitAction('delete')">Delete Selected</button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-fixed text-sm">
                    <colgroup>
                        <col class="w-16">
                        <col class="w-96">
                        <col class="w-20">
                        <col class="w-64">
                        <col class="w-40">
                        <col class="w-28">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="w-16 border-b px-7 py-4 text-left text-xs font-medium uppercase tracking-[0.16em]" style="border-color: var(--lr-border); color: var(--lr-subtle);">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="h-5 w-5 rounded-md border-transparent bg-[var(--lr-panel-strong)] text-[var(--lr-success)] focus:ring-0" @change="toggleAll($event)" :checked="allSelected">
                                </label>
                            </th>
                            <th class="w-96 border-b px-4 py-4 text-left text-xs font-medium uppercase tracking-[0.16em]" style="border-color: var(--lr-border); color: var(--lr-subtle);">URL</th>
                            <th class="w-20 border-b px-4 py-4 text-left text-xs font-medium uppercase tracking-[0.16em]" style="border-color: var(--lr-border); color: var(--lr-subtle);">Score</th>
                            <th class="w-64 border-b px-4 py-4 text-left text-xs font-medium uppercase tracking-[0.16em]" style="border-color: var(--lr-border); color: var(--lr-subtle);">Status</th>
                            <th class="w-40 border-b px-4 py-4 text-left text-xs font-medium uppercase tracking-[0.16em]" style="border-color: var(--lr-border); color: var(--lr-subtle);">Scanned</th>
                            <th class="w-28 border-b px-7 py-4 text-right text-xs font-medium uppercase tracking-[0.16em]" style="border-color: var(--lr-border); color: var(--lr-subtle);">Result</th>
                        </tr>
                    </thead>
                    <tbody class="launch-readiness-dashboard-table-body">
                        @forelse($scans as $scan)
                            <tr class="align-top">
                                <td class="w-16 border-b px-7 py-6" style="border-color: var(--lr-border);">
                                    <input type="checkbox" class="h-5 w-5 rounded-md border-transparent bg-[var(--lr-panel-strong)] text-[var(--lr-success)] focus:ring-0" value="{{ $scan->id }}" @change="toggleOne($event)" :checked="selected.includes('{{ $scan->id }}')">
                                </td>
                                <td class="w-96 border-b px-4 py-6 overflow-hidden" style="border-color: var(--lr-border);">
                                    <div class="block w-full truncate text-sm font-normal text-gray-700">{{ $scan->final_url ?: $scan->submitted_url }}</div>
                                </td>
                                <td class="w-20 border-b px-4 py-6" style="border-color: var(--lr-border);">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base font-normal text-gray-700">{{ $scan->launch_score }}</span>
                                    </div>
                                </td>
                                <td class="w-64 border-b px-4 py-6" style="border-color: var(--lr-border);">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium leading-4" style="border-color: color-mix(in srgb, var(--lr-success) 35%, transparent); background: color-mix(in srgb, var(--lr-success) 14%, transparent); color: var(--lr-success);">{{ $scan->passed_checks }} Passed</span>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium leading-4" style="border-color: color-mix(in srgb, var(--lr-warning) 35%, transparent); background: color-mix(in srgb, var(--lr-warning) 14%, transparent); color: var(--lr-warning);">{{ $scan->warning_checks }} Warnings</span>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium leading-4" style="{{ (int) $scan->failed_checks === 0
                                            ? 'border-color: rgb(209 213 219); background: rgb(249 250 251); color: rgb(107 114 128);'
                                            : 'border-color: color-mix(in srgb, var(--lr-danger) 35%, transparent); background: color-mix(in srgb, var(--lr-danger) 14%, transparent); color: var(--lr-danger);' }}">{{ $scan->failed_checks }} Errors</span>
                                    </div>
                                </td>
                                <td class="w-40 border-b px-4 py-6" style="border-color: var(--lr-border);">
                                    <div class="text-sm font-normal text-gray-700">{{ optional($scan->scanned_at)->format('F j, Y') }}</div>
                                    <div class="mt-1 text-xs font-normal text-gray-700">{{ optional($scan->scanned_at)->format('H:i') }}</div>
                                </td>
                                <td class="w-28 border-b px-7 py-6 text-right" style="border-color: var(--lr-border);">
                                    <div class="flex justify-end gap-4 text-sm font-normal">
                                        <form method="POST" action="{{ route('launch-readiness.dashboard.scans.recheck', ['toolSlug' => $toolSlug, 'toolScan' => $scan]) }}">
                                            @csrf
                                            <button type="submit" class="text-gray-700 transition hover:text-[var(--lr-success)]">Recheck</button>
                                        </form>
                                        <a href="{{ route('launch-readiness.results.show', ['toolSlug' => $toolSlug, 'toolScan' => $scan]) }}" class="text-gray-700 transition hover:text-[var(--lr-success)]">View</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-7 py-14 text-center">
                                    <p class="text-lg font-semibold text-[var(--lr-text)]">No tests yet</p>
                                    <p class="mt-2 text-sm text-[var(--lr-muted)]">Run your first manual test to populate the dashboard.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if($scans instanceof \Illuminate\Pagination\LengthAwarePaginator && $scans->hasPages())
            <div class="flex justify-end">
                {{ $scans->onEachSide(1)->links() }}
            </div>
        @endif

    </div>
@endsection
