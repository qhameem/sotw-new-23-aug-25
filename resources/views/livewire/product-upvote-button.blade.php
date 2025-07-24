<div>
    @if (!$product->is_promoted)
        <div x-data="upvote(
            {{ $product->is_upvoted_by_current_user ? 'true' : 'false' }},
            {{ $product->votes_count ?? 0 }},
            '{{ $product->id }}',
            '{{ $product->slug }}',
            {{ Auth::check() ? 'true' : 'false' }},
            '{{ csrf_token() }}'
        )" class="">
        
            <button type="button"
                    @click="toggleUpvote"
                    class="flex flex-col items-center justify-center w-12 aspect-square bg-white border border-gray-200 rounded-md shadow-sm text-xs text-gray-500 focus:outline-none">
                <svg class="h-4 w-4 fill-gray-300" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0" ></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M3 19h18a1.002 1.002 0 0 0 .823-1.569l-9-13c-.373-.539-1.271-.539-1.645 0l-9 13A.999.999 0 0 0 3 19z"></path></g></svg>
                <span x-text="votesCount" class="text-gray-800"></span>
            </button>
            <p x-show="errorMessage" x-text="errorMessage" class="text-red-500 text-xs mt-1"></p>
        </div>
    @endif
</div>