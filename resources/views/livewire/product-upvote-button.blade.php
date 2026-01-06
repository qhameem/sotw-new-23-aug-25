<div>
    @if (!$product->is_promoted)
        @php
            $isProductPage = request()->is('product/' . $product->slug);
        @endphp

        @if ($isProductPage)
            {{-- Alternate upvote button design for /product/{product_name} page --}}
            <div x-data="upvote(
                                        {{ $product->is_upvoted_by_current_user ? 'true' : 'false' }},
                                        {{ $product->votes_count ?? 0 }},
                                        '{{ $product->id }}',
                                        '{{ $product->slug }}',
                                        {{ Auth::check() ? 'true' : 'false' }},
                                        '{{ csrf_token() }}'
                                    )">
                {{-- START: Different design (customize this part later) --}}
                <button type="button" @click.stop="toggleUpvote"
                    class="flex flex-row items-center bg-primary-500 rounded-lg px-4 py-1.5 text-white text-sm font-semibold hover:bg-rose-600">

                    <span class="pr-2">
                        <svg class="size-4 fill-none stroke-white" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <g id="Shape / Triangle">
                                    <path id="Vector"
                                        d="M4.37891 15.1999C3.46947 16.775 3.01489 17.5634 3.08281 18.2097C3.14206 18.7734 3.43792 19.2851 3.89648 19.6182C4.42204 20.0001 5.3309 20.0001 7.14853 20.0001H16.8515C18.6691 20.0001 19.5778 20.0001 20.1034 19.6182C20.5619 19.2851 20.8579 18.7734 20.9172 18.2097C20.9851 17.5634 20.5307 16.775 19.6212 15.1999L14.7715 6.79986C13.8621 5.22468 13.4071 4.43722 12.8135 4.17291C12.2957 3.94236 11.704 3.94236 11.1862 4.17291C10.5928 4.43711 10.1381 5.22458 9.22946 6.79845L4.37891 15.1999Z"
                                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </g>
                            </g>
                        </svg>
                    </span>

                    <span class="pr-1">Upvote</span>
                    <div class="h-6 w-px bg-gray-100 mx-2"></div>
                    <template x-if="votesCount > 0">
                        <span x-text="votesCount" class="text-white pl-1.5 text-sm"></span>
                    </template>
                </button>
                <p x-show="errorMessage" x-text="errorMessage" class="text-red-500 text-xs mt-1"></p>
                {{-- END: Different design --}}
            </div>
        @else
            {{-- Default upvote button --}}
            <div x-data="upvote(
                                        {{ $product->is_upvoted_by_current_user ? 'true' : 'false' }},
                                        {{ $product->votes_count ?? 0 }},
                                        '{{ $product->id }}',
                                        '{{ $product->slug }}',
                                        {{ Auth::check() ? 'true' : 'false' }},
                                        '{{ csrf_token() }}'
                                    )" class="">

                <button type="button" @click.stop="toggleUpvote" :class="votesCount > 0 ? 'justify-between' : 'justify-center'"
                    class="flex flex-col items-center w-12 h-16 py-2.5 bg-white border border-gray-200 rounded-md shadow-sm text-xs text-gray-500 focus:outline-none transition-all duration-300 hover:border-rose-300 hover:shadow-[0_4px_12px_rgba(0,0,0,0.05)]">

                    <svg class="size-4 fill-none stroke-gray-200" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <g id="Shape / Triangle">
                                <path id="Vector"
                                    d="M4.37891 15.1999C3.46947 16.775 3.01489 17.5634 3.08281 18.2097C3.14206 18.7734 3.43792 19.2851 3.89648 19.6182C4.42204 20.0001 5.3309 20.0001 7.14853 20.0001H16.8515C18.6691 20.0001 19.5778 20.0001 20.1034 19.6182C20.5619 19.2851 20.8579 18.7734 20.9172 18.2097C20.9851 17.5634 20.5307 16.775 19.6212 15.1999L14.7715 6.79986C13.8621 5.22468 13.4071 4.43722 12.8135 4.17291C12.2957 3.94236 11.704 3.94236 11.1862 4.17291C10.5928 4.43711 10.1381 5.22458 9.22946 6.79845L4.37891 15.1999Z"
                                    stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                        </g>
                    </svg>

                    <template x-if="votesCount > 0">
                        <span x-text="votesCount" class="text-gray-800 font-medium"></span>
                    </template>
                </button>
                <p x-show="errorMessage" x-text="errorMessage" class="text-red-500 text-xs mt-1"></p>
            </div>
        @endif
    @endif
</div>