@php
    use App\Support\ProductLogo;
@endphp

@if($alternativeProducts->isNotEmpty())
    <section>
        <h2 class="text-base font-semibold text-gray-900">Featured alternatives</h2>

        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="divide-y divide-gray-100">
            @foreach($alternativeProducts as $alternative)
                @php
                    $alternativeLogo = ProductLogo::url($alternative);
                @endphp

                <a href="{{ route('products.show', ['product' => $alternative->slug]) }}"
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

                    <span class="flex shrink-0 items-center gap-1 text-xs font-medium text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-300" viewBox="0 0 24 24"
                            fill="currentColor" aria-hidden="true">
                            <path
                                d="M6 13H2c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h4c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zm16-4h-4c-.6 0-1 .4-1 1v12c0 .6.4 1 1 1h4c.6 0 1-.4 1-1V10c0-.6-.4-1-1-1zm-8-8h-4c-.6 0-1 .4-1 1v20c0 .6.4 1 1 1h4c.6 0 1-.4 1-1V2c0-.6-.4-1-1-1z" />
                        </svg>
                        <span>{{ number_format((int) ($alternative->impressions ?? 0)) }}</span>
                    </span>
                </a>
            @endforeach
            </div>
        </div>
    </section>
@endif
