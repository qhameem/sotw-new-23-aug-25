@extends('layouts.app')

@section('title', 'Changelog')

@php
    $activeChangelogModal = session('changelog_modal');
@endphp

@section('header-title')
    Changelog
@endsection

@section('actions')
    @role('admin')
        <div x-data="{ openingNewEntry: false }">
            <button
                type="button"
                @click="openingNewEntry = true; $dispatch('open-modal', { name: 'new-changelog-entry' }); setTimeout(() => openingNewEntry = false, 250)"
                x-bind:disabled="openingNewEntry"
                class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span x-show="!openingNewEntry">+ New Entry</span>
                <span x-cloak x-show="openingNewEntry" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                        <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                    </svg>
                    Opening...
                </span>
            </button>
        </div>
    @endrole
@endsection

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="max-w-4xl mx-auto">
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @forelse($changelogs as $date => $logs)
            <div class="mb-12">
                <h2 class="text-sm font-bold text-gray-700 border-b pb-3 mb-4">{{ $date }}</h2>
                <div class="space-y-7">
                    @foreach($logs as $log)
                        @php
                            $editModalName = 'edit-changelog-entry-' . $log->id;
                            $deleteModalName = 'delete-changelog-entry-' . $log->id;
                            $isEditModalActive = $activeChangelogModal === 'edit-' . $log->id;
                        @endphp
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-sm font-medium">{{ $log->title }}</h3>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-lg
                                    @switch($log->type)
                                        @case('added') bg-green-100 text-green-800 @break
                                        @case('changed') bg-yellow-100 text-yellow-800 @break
                                        @case('fixed') bg-blue-100 text-blue-800 @break
                                        @case('removed') bg-red-100 text-red-800 @break
                                    @endswitch
                                ">{{ ucfirst($log->type) }}</span>
                                @if($log->version)
                                    <span class="text-sm text-gray-500">{{ $log->version }}</span>
                                @endif
                            </div>
                            @if($log->description)
                                <div class="text-sm prose prose-sm max-w-none mt-2 text-gray-700">
                                    {!! $log->description !!}
                                </div>
                            @endif

                            @role('admin')
                                <div class="mt-3 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="$dispatch('open-modal', { name: '{{ $editModalName }}' })"
                                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="$dispatch('open-modal', { name: '{{ $deleteModalName }}' })"
                                        class="inline-flex items-center rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-200"
                                    >
                                        Delete
                                    </button>
                                </div>

                                @include('changelogs.partials.form-modal', [
                                    'modalName' => $editModalName,
                                    'show' => $isEditModalActive,
                                    'action' => route('changelog.update', $log),
                                    'heading' => 'Edit changelog entry',
                                    'subheading' => 'Update this changelog entry and save your changes.',
                                    'submitLabel' => 'Save Changes',
                                    'submitLoadingLabel' => 'Saving...',
                                    'entry' => $log,
                                    'method' => 'PATCH',
                                    'useOldInput' => $isEditModalActive,
                                ])

                                @include('changelogs.partials.delete-modal', [
                                    'modalName' => $deleteModalName,
                                    'entry' => $log,
                                ])
                            @endrole
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center">No changelog entries yet.</p>
        @endforelse
    </div>
</div>

@role('admin')
    @include('changelogs.partials.form-modal', [
        'modalName' => 'new-changelog-entry',
        'show' => $activeChangelogModal === 'create',
        'action' => route('changelog.store'),
        'heading' => 'Add changelog entry',
        'subheading' => 'Create a concise update for the public changelog.',
        'submitLabel' => 'Save Entry',
        'submitLoadingLabel' => 'Saving...',
        'entry' => null,
        'method' => null,
        'useOldInput' => $activeChangelogModal === 'create',
    ])
@endrole
@endsection
