@php
    $sidebarSnippets = \App\Models\CodeSnippet::where('location', 'sidebar')->get();
    $page = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp
<div class="space-y-6">
    <div class="sidebar-snippets-container w-full overflow-x-auto">
        @foreach ($sidebarSnippets as $snippet)
            @if ($snippet->page === 'all' || request()->routeIs(str_replace('.index', '.*', $snippet->page)))
                {!! html_entity_decode($snippet->code) !!}
            @endif
        @endforeach
    </div>
    @unless($product->user->hasRole('admin'))
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Publisher</h3>
            <div class="flex items-center gap-2">
                <img src="{{ $product->user->avatar() }}" alt="{{ $product->user->name }}"
                    class="size-6 rounded-full border border-gray-100">
                <div class="text-gray-800 text-sm font-medium">
                    {{ $product->user->name }}
                </div>
            </div>
        </div>
    @endunless

    @if($bestForCategories->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">This product is best for</h3>
            <div class="flex flex-wrap gap-1.5">
                @foreach($bestForCategories as $category)
                    <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                        class="text-xs text-gray-700 hover:text-gray-900 font-medium">
                        {{ $category->name }}@if(!$loop->last)<span class="text-gray-300 ml-1.5">•</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($pricingCategory)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Pricing Model</h3>
            <p class="text-xs text-gray-700 font-medium">{{ $pricingCategory->name }}</p>
        </div>
    @endif

    @if($product->price > 0)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Price</h3>
            <p class="text-xs text-gray-700 font-medium">
                {{ $product->currency }} {{ number_format($product->price, 2) }}
            </p>
        </div>
    @endif

    @if($product->techStacks->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Built with</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($product->techStacks as $techStack)
                    <span class="inline-flex items-center text-xs text-gray-700 font-medium bg-gray-50 px-2 py-0.5 rounded border border-gray-100">
                        {{ $techStack->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif


    @php
        $makerLinks = is_array($product->maker_links) ? $product->maker_links : json_decode($product->maker_links, true) ?? [];
    @endphp

    @if(!empty($makerLinks) || $product->x_account)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Links & Social</h3>
            <div class="space-y-2">
                @if($product->x_account)
                    <a href="https://x.com/{{ ltrim($product->x_account, '@') }}" target="_blank" rel="noopener" 
                       class="flex items-center gap-2 text-xs text-gray-700 hover:text-gray-900 font-medium group text-[11px]">
                        <svg class="size-3.5 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        <span>@ {{ ltrim($product->x_account, '@') }}</span>
                    </a>
                @endif

                @if(!empty($makerLinks))
                    @foreach($makerLinks as $link)
                        @php 
                            $host = parse_url($link, PHP_URL_HOST);
                            $displayLink = $host ? str_replace('www.', '', $host) : 'Extra link';
                        @endphp
                        <a href="{{ $link }}" target="_blank" rel="noopener" 
                           class="flex items-center gap-2 text-xs text-gray-700 hover:text-gray-900 font-medium group truncate text-[11px]">
                            <svg class="size-3.5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            <span>{{ $displayLink }}</span>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

</div>