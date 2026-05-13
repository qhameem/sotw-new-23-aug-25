@props([
    'modalName',
    'show' => false,
    'action',
    'heading',
    'subheading',
    'submitLabel',
    'submitLoadingLabel',
    'entry' => null,
    'method' => null,
    'useOldInput' => false,
])

@php
    $fieldIdPrefix = str_replace(['.', ' '], '-', $modalName);
    $plainDescription = $entry?->description
        ? preg_replace('/<br\s*\/?>/i', "\n", strip_tags($entry->description))
        : '';

    $releasedAtValue = $useOldInput
        ? old('released_at', $entry?->released_at?->format('Y-m-d') ?? now()->format('Y-m-d'))
        : ($entry?->released_at?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $typeValue = $useOldInput ? old('type', $entry?->type ?? 'added') : ($entry?->type ?? 'added');
    $titleValue = $useOldInput ? old('title', $entry?->title ?? '') : ($entry?->title ?? '');
    $versionValue = $useOldInput ? old('version', $entry?->version ?? '') : ($entry?->version ?? '');
    $descriptionValue = $useOldInput ? old('description', $plainDescription) : $plainDescription;
@endphp

<x-modal :name="$modalName" :show="$show" maxWidth="lg" focusable>
    <form
        method="POST"
        action="{{ $action }}"
        class="p-5 sm:p-5"
        x-data="{ submitting: false }"
        @submit="submitting = true"
    >
        @csrf
        @if ($method)
            @method($method)
        @endif

        <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-3">
            <div>
                <h2 class="text-base font-semibold text-gray-900">{{ $heading }}</h2>
                <p class="mt-1 text-xs text-gray-600">{{ $subheading }}</p>
            </div>

            <button
                type="button"
                @click="$dispatch('close-modal', '{{ $modalName }}')"
                x-bind:disabled="submitting"
                class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:cursor-not-allowed disabled:opacity-50"
                aria-label="Close"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div class="mt-5 space-y-4">
            @if ($show && $errors->changelogEntry->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Please fix the highlighted fields and try again.
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label :for="$fieldIdPrefix . '-released-at'" value="Release Date" />
                    <x-text-input
                        :id="$fieldIdPrefix . '-released-at'"
                        name="released_at"
                        type="date"
                        class="mt-1.5 block w-full text-sm"
                        :value="$releasedAtValue"
                        required
                    />
                    @if ($show)
                        <x-input-error :messages="$errors->changelogEntry->get('released_at')" class="mt-2" />
                    @endif
                </div>

                <div>
                    <x-input-label :for="$fieldIdPrefix . '-type'" value="Type" />
                    <select
                        id="{{ $fieldIdPrefix . '-type' }}"
                        name="type"
                        class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        required
                    >
                        <option value="added" @selected($typeValue === 'added')>Added</option>
                        <option value="changed" @selected($typeValue === 'changed')>Changed</option>
                        <option value="fixed" @selected($typeValue === 'fixed')>Fixed</option>
                        <option value="removed" @selected($typeValue === 'removed')>Removed</option>
                    </select>
                    @if ($show)
                        <x-input-error :messages="$errors->changelogEntry->get('type')" class="mt-2" />
                    @endif
                </div>
            </div>

            <div>
                <x-input-label :for="$fieldIdPrefix . '-title'" value="Title" />
                <x-text-input
                    :id="$fieldIdPrefix . '-title'"
                    name="title"
                    type="text"
                    class="mt-1.5 block w-full text-sm"
                    :value="$titleValue"
                    placeholder="What changed?"
                    required
                />
                @if ($show)
                    <x-input-error :messages="$errors->changelogEntry->get('title')" class="mt-2" />
                @endif
            </div>

            <div>
                <x-input-label :for="$fieldIdPrefix . '-version'" value="Version" />
                <x-text-input
                    :id="$fieldIdPrefix . '-version'"
                    name="version"
                    type="text"
                    class="mt-1.5 block w-full text-sm"
                    :value="$versionValue"
                    placeholder="Optional, for example v1.2.0"
                />
                @if ($show)
                    <x-input-error :messages="$errors->changelogEntry->get('version')" class="mt-2" />
                @endif
            </div>

            <div>
                <x-input-label :for="$fieldIdPrefix . '-description'" value="Description" />
                <textarea
                    id="{{ $fieldIdPrefix . '-description' }}"
                    name="description"
                    rows="4"
                    class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="Optional details for this entry"
                >{{ $descriptionValue }}</textarea>
                @if ($show)
                    <x-input-error :messages="$errors->changelogEntry->get('description')" class="mt-2" />
                @endif
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-3 border-t border-gray-200 pt-4">
            <x-secondary-button
                type="button"
                @click="$dispatch('close-modal', '{{ $modalName }}')"
                x-bind:disabled="submitting"
            >
                Cancel
            </x-secondary-button>
            <button
                type="submit"
                x-bind:disabled="submitting"
                class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span x-show="!submitting">{{ $submitLabel }}</span>
                <span x-cloak x-show="submitting" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                        <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                    </svg>
                    {{ $submitLoadingLabel }}
                </span>
            </button>
        </div>
    </form>
</x-modal>
