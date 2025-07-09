<button type="button"
        wire:click="toggleUpvote"
        class="inline-flex items-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium
               @if($hasUpvoted)
                   text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 border-transparent
               @else
                   text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 border-gray-300
               @endif">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
    </svg>
    Upvote <span class="ml-2 @if($hasUpvoted) text-primary-100 @else text-gray-500 @endif">{{ $votesCount }}</span>
</button>