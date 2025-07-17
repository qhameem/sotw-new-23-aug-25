<div class="sticky top-0 z-40 border-b bg-white border-gray-200">
    <div class="flex justify-between items-center px-4 py-[0.78rem]">
        <div>
            <h1 class="hidden md:block text-lg md:text-base font-semibold tracking-tight">{{ $title }}</h1>
        </div>
        <div class="flex items-center space-x-4">
            @if (isset($actions))
                {!! $actions !!}
            @endif
            <div class="md:hidden">
                <x-user-dropdown />
            </div>
        </div>
    </div>
    <hr class="">
    @if (isset($below_header))
        {{ $below_header }}
    @endif
</div>