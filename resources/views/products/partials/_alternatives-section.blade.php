@php
    use App\Support\ProductLogo;
@endphp

@include('products.partials._card-3d-hover-styles')

@if($alternativeProducts->isNotEmpty())
    <div class="product-alternatives-panel rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($alternativeProducts as $alternative)
                @php
                    $alternativeLogo = ProductLogo::url($alternative);
                    $alternativeTagline = $alternative->product_page_tagline ?: $alternative->tagline ?: $alternative->editorial_take;
                @endphp

                <a href="{{ route('products.show', ['product' => $alternative->slug]) }}" wire:navigate.hover
                    class="product-alternative-card product-3d-hover-card group flex min-w-0 items-center gap-4 rounded-xl border border-gray-200 bg-white p-4">
                    @if($alternativeLogo)
                        <img src="{{ $alternativeLogo }}" alt="{{ $alternative->name }} logo"
                            class="h-14 w-14 shrink-0 rounded-xl border border-gray-200 bg-white object-contain p-1.5">
                    @else
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-base font-semibold text-gray-500">
                            {{ ProductLogo::initial($alternative) }}
                        </div>
                    @endif

                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="truncate text-base font-semibold text-gray-900 transition group-hover:text-primary-700">
                                {{ $alternative->name }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-primary-500"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </span>

                        @if(filled($alternativeTagline))
                            <span class="mt-1 block truncate text-sm text-gray-500">
                                {{ $alternativeTagline }}
                            </span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endif
