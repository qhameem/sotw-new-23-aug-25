@extends('layouts.app')

@section('title', 'Discovered Outbound Links')

@section('header-title')
    Discovered Outbound Links
@endsection

@section('content')
    @php
        $policyService = app(\App\Services\OutboundLinkPolicyService::class);
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        @if(session('success'))
            <div class="rounded-lg bg-green-100 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Discovered Outbound Links</h1>
                <p class="mt-1 text-sm text-gray-500">Use this page to find outbound links in one place, then create dofollow exceptions when needed.</p>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('admin.outbound-links.occurrences.rescan') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        Run Rescan
                    </button>
                </form>
                <a href="{{ route('admin.outbound-links.rules.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Manage Rules
                </a>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <form method="GET" class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px_auto]">
                <div>
                    <label for="q" class="block text-sm font-medium text-gray-700">Search</label>
                    <input id="q" name="q" type="text" value="{{ request('q') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="Domain, URL, or source title">
                </div>
                <div>
                    <label for="source_type" class="block text-sm font-medium text-gray-700">Source Type</label>
                    <select id="source_type" name="source_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All source types</option>
                        @foreach($sourceTypes as $sourceType)
                            <option value="{{ $sourceType }}" @selected(request('source_type') === $sourceType)>{{ $sourceType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Domain</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">URL</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Current Rel</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Matched Rule</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($occurrences as $occurrence)
                            @php
                                $rule = $policyService->matchRule($occurrence->normalized_url, $occurrence->source_type);
                                $effectiveRel = $policyService->relStringForUrl($occurrence->normalized_url, $occurrence->source_type) ?? 'none';
                            @endphp
                            <tr>
                                <td class="px-4 py-4 align-top text-sm text-gray-700">{{ $occurrence->domain }}</td>
                                <td class="px-4 py-4 align-top text-sm">
                                    <a href="{{ $occurrence->normalized_url }}" target="_blank" rel="nofollow noopener noreferrer" class="break-all text-blue-600 hover:underline">
                                        {{ $occurrence->normalized_url }}
                                    </a>
                                    @if($occurrence->anchor_text)
                                        <div class="mt-1 text-xs text-gray-500">Anchor: {{ $occurrence->anchor_text }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-gray-700">
                                    <div class="font-medium">{{ $occurrence->source_type }}</div>
                                    @if($occurrence->source_title)
                                        <div class="mt-1 text-xs text-gray-500">{{ $occurrence->source_title }}</div>
                                    @endif
                                    @if($occurrence->source_admin_url)
                                        <a href="{{ $occurrence->source_admin_url }}" class="mt-2 inline-block text-xs text-primary-600 hover:underline">Open source</a>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-gray-700">
                                    <div>{{ $effectiveRel }}</div>
                                    @if($occurrence->detected_rel)
                                        <div class="mt-1 text-xs text-gray-500">Stored: {{ $occurrence->detected_rel }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-gray-700">
                                    {{ $rule?->name ?? 'No matching rule' }}
                                </td>
                                <td class="px-4 py-4 align-top text-sm">
                                    <div class="flex flex-col gap-2">
                                        <form action="{{ route('admin.outbound-links.occurrences.quick-allow') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="url" value="{{ $occurrence->normalized_url }}">
                                            <input type="hidden" name="mode" value="exact_url">
                                            <button type="submit" class="text-left text-sm font-medium text-primary-600 hover:text-primary-700">Make exact URL dofollow</button>
                                        </form>
                                        <form action="{{ route('admin.outbound-links.occurrences.quick-allow') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="url" value="{{ $occurrence->normalized_url }}">
                                            <input type="hidden" name="mode" value="domain">
                                            <button type="submit" class="text-left text-sm font-medium text-primary-600 hover:text-primary-700">Make domain dofollow</button>
                                        </form>
                                        <form action="{{ route('admin.outbound-links.occurrences.quick-allow') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="url" value="{{ $occurrence->normalized_url }}">
                                            <input type="hidden" name="mode" value="domain_path_prefix">
                                            <button type="submit" class="text-left text-sm font-medium text-primary-600 hover:text-primary-700">Make path dofollow</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No discovered outbound links matched this filter yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-4">
                {{ $occurrences->links() }}
            </div>
        </div>
    </div>
@endsection
