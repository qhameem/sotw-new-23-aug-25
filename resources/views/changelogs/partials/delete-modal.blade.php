@props([
    'modalName',
    'entry',
])

<x-modal :name="$modalName" :show="false" maxWidth="md" focusable>
    <form
        method="POST"
        action="{{ route('changelog.destroy', $entry) }}"
        class="p-5"
        x-data="{ deleting: false }"
        @submit="deleting = true"
    >
        @csrf
        @method('DELETE')

        <div class="border-b border-gray-200 pb-3">
            <h2 class="text-base font-semibold text-gray-900">Delete changelog entry</h2>
            <p class="mt-1 text-sm text-gray-600">This will permanently remove this changelog entry.</p>
        </div>

        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <p class="text-sm font-medium text-gray-900">{{ $entry->title }}</p>
            <p class="mt-1 text-xs text-gray-500">
                {{ ucfirst($entry->type) }}
                @if ($entry->version)
                    · {{ $entry->version }}
                @endif
                · {{ $entry->released_at->format('F j, Y') }}
            </p>
        </div>

        <div class="mt-5 flex justify-end gap-3">
            <x-secondary-button
                type="button"
                @click="$dispatch('close-modal', '{{ $modalName }}')"
                x-bind:disabled="deleting"
            >
                Cancel
            </x-secondary-button>
            <button
                type="submit"
                x-bind:disabled="deleting"
                class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 disabled:cursor-not-allowed disabled:opacity-70"
            >
                <span x-show="!deleting">Delete Entry</span>
                <span x-cloak x-show="deleting" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25" stroke-width="4"></circle>
                        <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                    </svg>
                    Deleting...
                </span>
            </button>
        </div>
    </form>
</x-modal>
