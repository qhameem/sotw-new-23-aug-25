@php
    use App\Support\ProductLogo;
@endphp

@if($alternativeProducts->isNotEmpty())
    <section>
        <h3 class="text-base font-semibold text-gray-900">Featured alternatives</h3>

        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="divide-y divide-gray-100">
            @foreach($alternativeProducts as $alternative)
                @php
                    $alternativeLogo = ProductLogo::url($alternative);
                @endphp

                <a href="{{ route('products.show', ['product' => $alternative->slug]) }}" wire:navigate.hover
                    class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0 hover:text-primary-600">
                    <div class="flex min-w-0 items-center gap-3">
                        @if($alternativeLogo)
                            <img src="{{ $alternativeLogo }}" alt="{{ $alternative->name }} logo"
                                class="h-10 w-10 rounded-xl border border-gray-100 object-contain">
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-sm font-semibold text-gray-500">
                                {{ ProductLogo::initial($alternative) }}
                            </div>
                        @endif

                        <span class="truncate text-sm font-medium text-gray-900">
                            {{ $alternative->name }}
                        </span>
                    </div>

                    <span class="flex shrink-0 items-center gap-1 text-xs font-medium text-gray-400">
                        <span class="hidden max-w-24 truncate lg:inline">{{ $alternative->primary_category_label ?: 'View' }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </span>
                </a>
            @endforeach

                <a href="{{ route('pseo.alternatives', $product->slug) }}" wire:navigate.hover
                    class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 text-gray-400 transition-colors hover:text-gray-500">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-100 bg-white p-2 shadow-sm" aria-hidden="true">
                            <img src="{{ asset('images/icons/alternatives-list.svg') }}" alt="" class="h-full w-full object-contain">
                        </div>

                        <div class="min-w-0 flex items-center gap-2">
                            <span class="block text-sm font-medium">
                                View all <span class="text-gray-600">{{ $product->name }}</span> alternatives
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>
@endif
