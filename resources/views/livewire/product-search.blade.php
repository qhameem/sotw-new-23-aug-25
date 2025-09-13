<div>
    <input
        wire:model.live.debounce.300ms="query"
        type="text"
        placeholder="Search..."
        class="w-full shadow-sm px-3 py-1 border border-gray-300 text-sm rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent placeholder-gray-600 placeholder:text-sm"
        @focus="$dispatch('search-focus-changed', true)"
        @blur="$dispatch('search-focus-changed', false)"
    >
</div>
