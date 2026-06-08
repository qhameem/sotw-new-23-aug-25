@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true])

@section('title', 'Search History')

@section('header-title')
    Search History ({{ $searchLogCount }})
@endsection

@section('content')
    <div class="w-full px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Search History
                        </h3>
                        <div class="mt-2 max-w-2xl text-sm text-gray-500">
                            <p>Final search terms from the top navigation search, including signed-in users or guest IP/location details.</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Latest {{ $searchLogs->count() }} of {{ $searchLogs->total() }}</p>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Search</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">IP</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Location</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($searchLogs as $searchLog)
                                <tr>
                                    <td class="px-4 py-4 align-top text-sm font-medium text-gray-900">
                                        <div>{{ $searchLog->search_term }}</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $searchLog->source }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm text-gray-700">
                                        @if ($searchLog->user)
                                            <a href="{{ route('admin.users.show', $searchLog->user) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                                {{ $searchLog->user->name }}
                                            </a>
                                            <div class="mt-1 text-xs text-gray-500">{{ $searchLog->user->email }}</div>
                                        @else
                                            <span class="font-medium text-gray-900">Guest</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm text-gray-700">
                                        {{ $searchLog->ip_address ?: 'Unavailable' }}
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm text-gray-700">
                                        @php
                                            $locationParts = array_values(array_filter([
                                                $searchLog->city,
                                                $searchLog->country_name ?: $searchLog->country_code,
                                            ]));
                                        @endphp
                                        {{ count($locationParts) ? implode(', ', $locationParts) : 'Unavailable' }}
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm text-gray-700">
                                        <div>{{ $searchLog->created_at?->format('Y-m-d H:i:s') }} UTC</div>
                                        <div class="mt-1 text-xs text-gray-500">{{ $searchLog->created_at?->diffForHumans() }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                        No search activity tracked yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($searchLogs->hasPages())
                    <div class="mt-4">
                        {{ $searchLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
