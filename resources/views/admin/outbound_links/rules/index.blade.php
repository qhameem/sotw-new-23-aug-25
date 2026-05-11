@extends('layouts.app')

@section('title', 'Outbound Link Rules')

@section('header-title')
    Outbound Link Rules
@endsection

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        @if(session('success'))
            <div class="rounded-lg bg-green-100 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-red-100 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Outbound Link Rules</h1>
                <p class="mt-1 text-sm text-gray-500">Default outbound links are nofollow. Add exceptions here when you want specific URLs or domains to become dofollow.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.outbound-links.occurrences.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Browse Discovered Links
                </a>
                <a href="{{ route('admin.settings.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Back to Settings
                </a>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-gray-900">Create Rule</h2>
            <form action="{{ route('admin.outbound-links.rules.store') }}" method="POST" class="mt-5 grid gap-4 md:grid-cols-2">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Rule Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $prefill['name']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label for="pattern" class="block text-sm font-medium text-gray-700">Pattern</label>
                    <input id="pattern" name="pattern" type="text" value="{{ old('pattern', $prefill['pattern']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="example.com or https://example.com/path">
                </div>

                <div>
                    <label for="match_type" class="block text-sm font-medium text-gray-700">Match Type</label>
                    <select id="match_type" name="match_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach($matchTypes as $matchType)
                            <option value="{{ $matchType }}" @selected(old('match_type', $prefill['match_type']) === $matchType)>{{ $matchType }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="source_scope" class="block text-sm font-medium text-gray-700">Source Scope</label>
                    <select id="source_scope" name="source_scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach($sourceScopes as $scope)
                            <option value="{{ $scope }}" @selected(old('source_scope', $prefill['source_scope']) === $scope)>{{ $scope }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                    <input id="priority" name="priority" type="number" min="0" max="999999" value="{{ old('priority', $prefill['priority']) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('notes', $prefill['notes']) }}</textarea>
                </div>

                <div class="md:col-span-2 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @php
                        $checkboxes = [
                            'rel_nofollow' => 'Nofollow',
                            'rel_ugc' => 'UGC',
                            'rel_sponsored' => 'Sponsored',
                            'rel_noopener' => 'Noopener',
                            'rel_noreferrer' => 'Noreferrer',
                            'is_active' => 'Active',
                        ];
                    @endphp
                    @foreach($checkboxes as $field => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                            <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                @checked(old($field, $prefill[$field] ?? ($field === 'is_active')))>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        Save Rule
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-gray-900">Existing Rules</h2>
            <div class="mt-5 space-y-4">
                @forelse($rules as $rule)
                    <form action="{{ route('admin.outbound-links.rules.update', $rule) }}" method="POST" class="rounded-xl border border-gray-200 p-4">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Name</label>
                                <input name="name" type="text" value="{{ old("name.{$rule->id}", $rule->name) }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Pattern</label>
                                <input name="pattern" type="text" value="{{ old("pattern.{$rule->id}", $rule->pattern) }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Match Type</label>
                                <select name="match_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    @foreach($matchTypes as $matchType)
                                        <option value="{{ $matchType }}" @selected($rule->match_type === $matchType)>{{ $matchType }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Source Scope</label>
                                <select name="source_scope" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                    @foreach($sourceScopes as $scope)
                                        <option value="{{ $scope }}" @selected($rule->source_scope === $scope)>{{ $scope }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</label>
                                <input name="priority" type="number" min="0" max="999999" value="{{ $rule->priority }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                            <div class="xl:col-span-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</label>
                                <input name="notes" type="text" value="{{ $rule->notes }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            @foreach($checkboxes as $field => $label)
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700">
                                    <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" @checked($rule->{$field})>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="submit" class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                                Update
                            </button>
                        </div>
                    </form>
                    <form action="{{ route('admin.outbound-links.rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('Delete this rule?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">Delete rule</button>
                    </form>
                @empty
                    <p class="text-sm text-gray-500">No outbound link rules have been created yet.</p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $rules->links() }}
            </div>
        </div>
    </div>
@endsection
