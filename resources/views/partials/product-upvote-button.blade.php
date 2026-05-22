<div>
    @if (!$product->is_promoted)
        @php
            $isProductPage = request()->is('product/' . $product->slug);
            $compact = $compact ?? false;
        @endphp

        @if ($isProductPage)
            <div x-data="upvote(
                {{ $product->is_upvoted_by_current_user ? 'true' : 'false' }},
                {{ $product->votes_count ?? 1 }},
                '{{ $product->id }}',
                '{{ $product->slug }}',
                {{ Auth::check() ? 'true' : 'false' }},
                '{{ csrf_token() }}'
            )" class="w-full md:w-auto">
                <button type="button" @click.stop="toggleUpvote"
                    class="{{ $compact
                        ? 'flex min-h-8 w-full flex-row items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-500 border border-gray-300 transition-colors duration-200 hover:bg-gray-100 md:w-auto'
                        : 'flex min-h-[48px] w-full flex-row items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-gray-100 md:min-h-0 md:w-auto md:py-2' }}">

                    <span class="pr-2">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M4.37891 15.1999C3.46947 16.775 3.01489 17.5634 3.08281 18.2097C3.14206 18.7734 3.43792 19.2851 3.89648 19.6182C4.42204 20.0001 5.3309 20.0001 7.14853 20.0001H16.8515C18.6691 20.0001 19.5778 20.0001 20.1034 19.6182C20.5619 19.2851 20.8579 18.7734 20.9172 18.2097C20.9851 17.5634 20.5307 16.775 19.6212 15.1999L14.7715 6.79986C13.8621 5.22468 13.4071 4.43722 12.8135 4.17291C12.2957 3.94236 11.704 3.94236 11.1862 4.17291C10.5928 4.43711 10.1381 5.22458 9.22946 6.79845L4.37891 15.1999Z" />
                        </svg>
                    </span>

                    <span class="pr-1">Upvote</span>
                    <div class="mx-2 h-4 w-px bg-gray-500"></div>
                    <span x-text="votesCount" class="pl-1 text-sm text-gray-500"></span>
                </button>
                <p x-show="errorMessage" x-text="errorMessage" class="mt-1 text-xs text-red-500"></p>
            </div>
        @else
            <div x-data="upvote(
                {{ $product->is_upvoted_by_current_user ? 'true' : 'false' }},
                {{ $product->votes_count ?? 1 }},
                '{{ $product->id }}',
                '{{ $product->slug }}',
                {{ Auth::check() ? 'true' : 'false' }},
                '{{ csrf_token() }}'
            )" class="">

                <button type="button" @click.stop="toggleUpvote"
                    class="group flex h-16 w-16 flex-col items-center justify-center rounded-xl border border-slate-200 transition-colors duration-200 hover:border-slate-300">

                    <svg class="h-5 w-5 transition-colors duration-200"
                        :class="isUpvoted ? 'text-rose-600 fill-rose-600' : 'text-slate-300 group-hover:text-slate-400'"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path
                            d="M4.37891 15.1999C3.46947 16.775 3.01489 17.5634 3.08281 18.2097C3.14206 18.7734 3.43792 19.2851 3.89648 19.6182C4.42204 20.0001 5.3309 20.0001 7.14853 20.0001H16.8515C18.6691 20.0001 19.5778 20.0001 20.1034 19.6182C20.5619 19.2851 20.8579 18.7734 20.9172 18.2097C20.9851 17.5634 20.5307 16.775 19.6212 15.1999L14.7715 6.79986C13.8621 5.22468 13.4071 4.43722 12.8135 4.17291C12.2957 3.94236 11.704 3.94236 11.1862 4.17291C10.5928 4.43711 10.1381 5.22458 9.22946 6.79845L4.37891 15.1999Z" />
                    </svg>

                    <span x-text="votesCount" class="mt-2 text-xs font-regular leading-none tracking-tight transition-colors duration-200"
                        :class="isUpvoted ? 'text-rose-600' : 'text-slate-800'"></span>
                </button>
                <p x-show="errorMessage" x-text="errorMessage" class="mt-1 text-xs text-red-500"></p>
            </div>
        @endif
    @endif
</div>
