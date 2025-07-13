<div class="sticky top-0 z-10 bg-white border-b border-gray-200">
    <div class="flex justify-between items-center px-4 py-[0.78rem]">
        <div>
            <h1 class="text-lg md:text-base font-semibold tracking-tight">{{ $title }}</h1>
        </div>
        <div>
            @if (isset($actions))
                {{ $actions }}
            @endif
        </div>
    </div>
    <hr class="">
    @if (isset($below_header))
        {{ $below_header }}
    @endif
</div>