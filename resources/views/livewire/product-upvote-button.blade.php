<button type="button"
        wire:click="toggleUpvote"
        class="inline-flex items-center px-4 py-1 border rounded-md shadow-sm text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 focus:outline-none border-transparent">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
    </svg>
    Upvote
    <span class="ml-2 pl-2 border-l border-gray-600">{{ $votesCount }}</span>
</button>