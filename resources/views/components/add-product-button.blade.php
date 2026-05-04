@props([
    'compact' => false,
])

@php
    $targetUrl = route('products.create');
    $isGuest = !auth()->check();
@endphp

<a
    href="{{ $targetUrl }}"
    x-data="{ loading: false }"
    x-bind:aria-busy="loading"
    @click.prevent="
        if (@js($isGuest)) {
            $dispatch('open-modal', { name: 'login-required-modal', url: @js($targetUrl) });
            return;
        }

        loading = true;
        window.location.href = @js($targetUrl);
    "
    {{ $attributes->class([
        'relative inline-flex min-h-9 items-center justify-center gap-2 rounded-md border-2 border-gray-200 bg-white px-3 py-1 text-gray-700 shadow-sm transition duration-300 hover:bg-gray-50 hover:text-gray-900',
        'text-sm font-semibold' => $compact,
        'text-base font-semibold' => !$compact,
    ]) }}
>
    <span
        class="inline-flex items-center gap-2 whitespace-nowrap transition-opacity duration-150"
        :class="loading ? 'opacity-0' : 'opacity-100'"
    >
        @if ($compact)
            <span>Submit</span>
        @else
            <span class="hidden lg:inline-flex items-center gap-1.5">
                <svg class="h-5 w-5 shrink-0 fill-gray-700" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <g stroke-width="0"></g>
                    <g stroke-linecap="round" stroke-linejoin="round"></g>
                    <g>
                        <title>plus_circle [#1427]</title>
                        <desc>Cricled Plus Icon.</desc>
                        <defs></defs>
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g transform="translate(-179.000000, -600.000000)" fill="#4d4d4d">
                                <g transform="translate(56.000000, 160.000000)">
                                    <path d="M137.7,450 C137.7,450.552 137.2296,451 136.65,451 L134.55,451 L134.55,453 C134.55,453.552 134.0796,454 133.5,454 C132.9204,454 132.45,453.552 132.45,453 L132.45,451 L130.35,451 C129.7704,451 129.3,450.552 129.3,450 C129.3,449.448 129.7704,449 130.35,449 L132.45,449 L132.45,447 C132.45,446.448 132.9204,446 133.5,446 C134.0796,446 134.55,446.448 134.55,447 L134.55,449 L136.65,449 C137.2296,449 137.7,449.448 137.7,450 M133.5,458 C128.86845,458 125.1,454.411 125.1,450 C125.1,445.589 128.86845,442 133.5,442 C138.13155,442 141.9,445.589 141.9,450 C141.9,454.411 138.13155,458 133.5,458 M133.5,440 C127.70085,440 123,444.477 123,450 C123,455.523 127.70085,460 133.5,460 C139.29915,460 144,455.523 144,450 C144,444.477 139.29915,440 133.5,440"></path>
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
                <span>Submit product</span>
            </span>
            <span class="lg:hidden">Submit</span>
        @endif
    </span>

    <span
        x-show="loading"
        x-cloak
        class="absolute inset-0 inline-flex items-center justify-center text-current"
        style="display: none;"
    >
        <span class="inline-flex items-center justify-center gap-[3px]" aria-hidden="true">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.32s]"></span>
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current [animation-delay:-0.16s]"></span>
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current"></span>
        </span>
    </span>
</a>
