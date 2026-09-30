@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true])

@section('title', 'System Errors')

@section('header-title')
    System Errors
@endsection

@section('content')
<div class="w-full px-4 py-10 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">System Errors</h1>
            <p class="mt-1 text-sm text-gray-500">Deduplicated application errors with occurrence counts and debugging context.</p>
        </div>
        <form method="GET" class="flex flex-wrap gap-2">
            <select name="severity" class="rounded-md border-gray-300 text-sm">
                <option value="">All severities</option>
                @foreach (['warning', 'error', 'critical'] as $severity)
                    <option value="{{ $severity }}" @selected(request('severity') === $severity)>{{ ucfirst($severity) }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700">
                <input type="checkbox" name="unresolved" value="1" @checked(request()->boolean('unresolved'))>
                Unresolved only
            </label>
            <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="space-y-4">
        @forelse ($reports as $report)
            <article id="report-{{ $report->id }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm {{ request('report') == $report->id ? 'ring-2 ring-red-300' : '' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $report->severity === 'critical' ? 'bg-red-100 text-red-800' : ($report->severity === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-orange-100 text-orange-800') }}">{{ ucfirst($report->severity) }}</span>
                            <span class="text-xs font-medium text-gray-500">{{ $report->source }}</span>
                            <span class="text-xs text-gray-400">{{ $report->occurrence_count }} occurrence{{ $report->occurrence_count === 1 ? '' : 's' }}</span>
                            @if ($report->resolved_at)<span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">Resolved</span>@endif
                        </div>
                        <h2 class="mt-2 text-base font-semibold text-gray-900">{{ $report->summary }}</h2>
                    </div>
                    @unless ($report->resolved_at)
                        <form method="POST" action="{{ route('admin.errors.resolve', $report) }}">
                            @csrf
                            @method('PATCH')
                            <button class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Mark resolved</button>
                        </form>
                    @endunless
                </div>

                @if ($report->details)
                    <pre class="mt-4 max-h-64 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $report->details }}</pre>
                @endif

                @if ($report->context)
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs font-semibold text-gray-600">Context</summary>
                        <pre class="mt-2 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-xs text-gray-700">{{ json_encode($report->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif

                <div class="mt-3 text-xs text-gray-400">
                    First: {{ $report->first_occurred_at->format('M j, Y g:i A') }} · Last: {{ $report->last_occurred_at->diffForHumans() }}
                    @if ($report->user) · User: {{ $report->user->name }} ({{ $report->user->email }}) @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">No system errors found.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $reports->links() }}</div>
</div>
@endsection
