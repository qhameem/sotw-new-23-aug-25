@php
    use App\Support\ProductLogo;
@endphp

@if($alternativeProducts->isNotEmpty())
    <div class="grid gap-4 lg:grid-cols-3">
        @foreach($alternativeProducts as $alternative)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    @if($alternative->logo_url)
                        <img src="{{ $alternative->logo_url }}" alt="{{ $alternative->name }} logo"
                            class="h-12 w-12 rounded-xl border border-gray-100 object-contain">
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-sm font-semibold text-gray-500">
                            {{ ProductLogo::initial($alternative) }}
                        </div>
                    @endif

                    <div class="min-w-0">
                        <a href="{{ route('products.show', ['product' => $alternative->slug]) }}" wire:navigate.hover
                            class="text-base font-semibold text-gray-900 hover:text-primary-600">
                            {{ $alternative->name }}
                        </a>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ \Illuminate\Support\Str::limit($alternative->product_page_tagline ?: $alternative->tagline, 90) }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                    @if($alternative->primary_category_label)
                        <span class="rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-gray-700">
                            {{ $alternative->primary_category_label }}
                        </span>
                    @endif

                    @if($alternative->pricing_label)
                        <span class="rounded-full border border-green-200 bg-green-50 px-2.5 py-1 text-green-700">
                            {{ $alternative->pricing_label }}
                        </span>
                    @endif

                    @if($alternative->best_for_label)
                        <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-blue-700">
                            {{ $alternative->best_for_label }}
                        </span>
                    @endif
                </div>

                <p class="mt-4 text-sm leading-6 text-gray-600">
                    {{ \Illuminate\Support\Str::limit($alternative->editorial_take ?: $alternative->match_summary ?: $alternative->tagline, 150) }}
                </p>

                @if(!empty($alternative->feature_highlights))
                    <ul class="mt-4 space-y-2 text-sm leading-6 text-gray-600">
                        @foreach(array_slice($alternative->feature_highlights, 0, 3) as $feature)
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-primary-500"></span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="mt-5 flex gap-2">
                    <a href="{{ route('products.show', ['product' => $alternative->slug]) }}" wire:navigate.hover
                        class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900">
                        View Product
                    </a>
                    <a href="{{ route('pseo.compare', ['params' => $product->slug . '-vs-' . $alternative->slug]) }}"
                        class="inline-flex flex-1 items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
                        style="background-color: var(--color-primary-500, #0091FF);">
                        Compare
                    </a>
                </div>
            </article>
        @endforeach
    </div>
@endif
