<div class="sticky top-0 z-40  bg-white border-gray-200">
    <div class="flex justify-between items-center px-4 py-[0.78rem]">
        <div>
            <div class="flex items-center">
                <img src="{{ asset('favicon/logo.svg') }}" alt="Favicon" class="mobile-favicon mr-2 w-10 h-10 md:hidden">
                <h1 class="text-base md:text-xl font-semibold text-gray-600">{{ $title }}</h1>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            @if (isset($actions))
                {!! $actions !!}
            @endif
            <div class="md:hidden">
                <x-user-dropdown />
            </div>
            @push('styles')
            <style>
                .mobile-favicon {
                    width: 16px;
                    height: 16px;
                    vertical-align: middle;
                }
            </style>
            @endpush
        </div>
    </div>
    @if (isset($below_header))
        {{ $below_header }}
    @endif
    <hr class="md:hidden">
</div>