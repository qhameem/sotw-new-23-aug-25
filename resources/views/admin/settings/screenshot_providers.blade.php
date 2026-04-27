@extends('layouts.app')

@section('title', 'Screenshot Provider Debug')

@section('header-title')
    Screenshot Provider Debug
@endsection

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Screenshot Provider Quotas</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Current weighted rotation, local usage counters, and remaining free-tier quota.
                </p>
            </div>
            <a href="{{ route('admin.settings.index') }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Back to Settings
            </a>
        </div>

        <div class="rounded-xl border border-blue-100 bg-blue-50 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-blue-900">Next Weighted Attempt Order</h3>
            @if ($availableProviderOrder !== [])
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($availableProviderOrder as $provider)
                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-sm font-medium text-blue-800 ring-1 ring-blue-200">
                            {{ $loop->iteration }}. {{ $provider }}
                        </span>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-sm text-blue-900">
                    No configured provider currently has remaining local quota.
                </p>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Provider</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Configured</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Period</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Weight</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Used</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Remaining</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Limit</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Resets</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($providerSnapshots as $snapshot)
                            <tr>
                                <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ $snapshot['name'] }}</td>
                                <td class="px-4 py-4 text-sm">
                                    @if ($snapshot['configured'])
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Yes</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ ucfirst($snapshot['period']) }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ $snapshot['weight'] }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ $snapshot['used'] }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ $snapshot['remaining'] }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">{{ $snapshot['limit'] }}</td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    <div>{{ \Illuminate\Support\Carbon::parse($snapshot['reset_at'])->format('Y-m-d H:i') }}</div>
                                    <div class="text-xs text-gray-500">{{ $snapshot['period_key'] }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                                    No screenshot providers are configured in the app settings.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700">Configured Provider List</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($configuredProviders as $provider)
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                        {{ $provider }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@endsection
