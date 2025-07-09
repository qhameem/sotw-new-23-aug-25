<div class="relative" x-data="{ tooltip: false }">
    <a href="{{ route('promote') }}"
       @if($disabled)
           x-on:click.prevent
           @mouseenter="tooltip = true"
           @mouseleave="tooltip = false"
       @endif
       class="inline-block text-center w-full sm:w-auto px-4 py-1 border border-primary-500 rounded-lg text-sm font-medium text-primary-500 bg-white hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150 @if($disabled) opacity-50 cursor-not-allowed @endif">
        <span>Feature your product &rarr;</span>
    </a>
    <div x-show="tooltip && {{ $disabled ? 'true' : 'false' }}"
         class="absolute z-10 w-48 p-2 -mt-1 text-sm text-white bg-gray-800 rounded-lg shadow-lg"
         style="display: none;">
        All premium spots are currently filled.
    </div>
</div>