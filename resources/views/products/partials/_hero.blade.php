@php
    use App\Support\ProductLogo;

    $productLogo = ProductLogo::storedUrl($product);
@endphp

<div class="flex flex-col gap-4">
    <div class="flex items-start gap-4 md:gap-5">
        <div class="flex-shrink-0">
            @if(isset($isAdminView) && $isAdminView)
                <div @click="editingLogo = true" class="cursor-pointer">
                    <template x-if="!editingLogo">
                        @if($product->logo)
                            <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                                alt="{{ $product->name }} logo" class="h-14 w-14 rounded-xl object-contain md:h-[100px] md:w-[100px]">
                        @elseif($product->link)
                            <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}"
                                alt="{{ $product->name }} favicon" class="h-14 w-14 rounded-xl object-contain md:h-[100px] md:w-[100px]">
                        @endif
                    </template>
                    <template x-if="editingLogo">
                        <div class="mt-2">
                            <input type="file" class="text-xs">
                            <div class="mt-1 flex gap-2">
                                <button @click.stop="updateProduct(); editingLogo = false"
                                    class="rounded bg-primary-500 px-2 py-1 text-[10px] text-white">Save</button>
                                <button @click.stop="editingLogo = false"
                                    class="rounded bg-gray-200 px-2 py-1 text-[10px]">Cancel</button>
                            </div>
                        </div>
                    </template>
                </div>
            @else
                @if($productLogo)
                    <img src="{{ $productLogo }}"
                        alt="{{ $product->name }} logo" class="h-14 w-14 rounded-xl object-contain md:h-[100px] md:w-[100px]">
                @else
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gray-100 text-lg font-semibold text-gray-500 md:h-[100px] md:w-[100px] md:text-3xl">
                        {{ ProductLogo::initial($product) }}
                    </div>
                @endif
            @endif
        </div>

        <div class="flex min-w-0 flex-1 flex-col gap-1 md:min-h-[100px] md:justify-between">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <h1 class="site-heading-text min-w-0 break-words text-xl font-bold text-gray-900 md:text-2xl">
                    @if(isset($isAdminView) && $isAdminView)
                        <span x-show="!editingName" @click="editingName = true" x-text="name"></span>
                        <input x-show="editingName" x-model="name" @keydown.enter="updateProduct(); editingName = false"
                            @keydown.escape="editingName = false" class="form-input">
                    @else
                        {{ $product->name }}
                    @endif
                </h1>

                @if(Auth::check() && Auth::user()->hasRole('admin') && !(isset($isAdminView) && $isAdminView))
                    <a href="{{ route('admin.products.edit', $product) }}"
                        class="rounded-sm text-sm font-medium text-primary-600 underline decoration-transparent underline-offset-4 transition hover:decoration-current focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        Edit
                    </a>
                @endif
            </div>

            <div class="min-w-0 max-w-4xl">
                <p class="site-body-text text-[13px] leading-snug text-gray-800 md:text-[15px]">
                    @if(isset($isAdminView) && $isAdminView)
                        <span x-show="!editingProductPageTagline" @click="editingProductPageTagline = true"
                            x-text="product_page_tagline"></span>
                        <input x-show="editingProductPageTagline" x-model="product_page_tagline"
                            @keydown.enter="updateProduct(); editingProductPageTagline = false"
                            @keydown.escape="editingProductPageTagline = false" class="form-input">
                    @else
                        {{ $product->product_page_tagline ?: $product->tagline }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-gray-500">
                @include('products.partials._launch-metadata')
                @include('products.partials._hero-categories')
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3 border-t border-gray-200 pt-4">
        <x-products.visit-website-button :product="$product" surface="product_details" :label="'Visit ' . $product->name" class="min-w-0 md:min-w-[140px]" content-class="min-w-0 break-words" />

        <div
            x-data="{ saved: {{ $isSavedByCurrentUser ? 'true' : 'false' }} }"
            @product-collections-synced.window="saved = $event.detail.isSaved"
            class="group relative shrink-0"
        >
            <button
                type="button"
                @click="{{ Auth::check() ? "\$dispatch('open-modal', { name: 'product-save-modal' })" : "\$dispatch('open-modal', { name: 'login-required-modal' })" }}"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md border-2 border-gray-950 bg-white text-gray-900 shadow-[0_4px_0_#030712,0_8px_14px_rgba(15,23,42,0.14)] transition duration-150 hover:-translate-y-0.5 active:translate-y-0.5 active:shadow-none"
                :class="saved ? 'bg-gray-100 text-gray-500 hover:bg-gray-200' : 'hover:bg-gray-700 hover:text-white'"
                aria-label="Save product"
            >
                <svg x-show="!saved" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="M5 4.75A1.75 1.75 0 0 1 6.75 3h10.5A1.75 1.75 0 0 1 19 4.75V21l-7-4-7 4V4.75Z" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12 8.25v5.5M9.25 11h5.5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <svg x-show="saved" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" aria-hidden="true" style="display: none;">
                    <path d="M6.75 3h10.5A1.75 1.75 0 0 1 19 4.75V21l-7-4-7 4V4.75A1.75 1.75 0 0 1 6.75 3Z" stroke-width="1.2" stroke-linejoin="round" />
                </svg>
            </button>

            <span
                x-show="!saved"
                x-cloak
                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 shadow-sm group-hover:block"
                style="display: none;"
            >
                Add to collection
            </span>
        </div>
    </div>
</div>
