@php
    use App\Support\ProductLogo;

    $productLogo = ProductLogo::storedUrl($product);
@endphp

<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:gap-5">
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

    <div class="flex min-w-0 flex-1 flex-col gap-4 md:flex-row md:items-start md:justify-between md:gap-6">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <h1 class="site-heading-text text-xl font-bold text-gray-900 md:text-2xl">
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

            <div class="mt-1 min-w-0 max-w-4xl">
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

            <div class="mt-2 flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2">
                @php
                    $generalCategories = $product->categories->filter(function ($cat) {
                        return !$cat->types->contains('name', 'Pricing')
                            && !$cat->types->contains('name', 'Best for')
                            && !$cat->types->contains('name', 'Use Case')
                            && !$cat->types->contains('name', 'Use Cases')
                            && !$cat->types->contains('name', 'Platform');
                    });
                @endphp
                @if($generalCategories->isNotEmpty())
                    <svg class="h-4 w-4 flex-shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="M17 10H19C21 10 22 9 22 7V5C22 3 21 2 19 2H17C15 2 14 3 14 5V7C14 9 15 10 17 10Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M5 22H7C9 22 10 21 10 19V17C10 15 9 14 7 14H5C3 14 2 15 2 17V19C2 21 3 22 5 22Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M6 10C8.20914 10 10 8.20914 10 6C10 3.79086 8.20914 2 6 2C3.79086 2 2 3.79086 2 6C2 8.20914 3.79086 10 6 10Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M18 22C20.2091 22 22 20.2091 22 18C22 15.7909 20.2091 14 18 14C15.7909 14 14 15.7909 14 18C14 20.2091 15.7909 22 18 22Z" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                @endif
                @foreach($generalCategories as $category)
                    <a href="{{ route('categories.show', ['category' => $category->slug]) }}" wire:navigate.hover
                        class="text-[0.65rem] font-medium leading-5 text-gray-500 underline decoration-gray-300 underline-offset-4 transition-colors hover:text-gray-800 hover:decoration-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 md:text-xs">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>

        <div class="flex w-full items-center gap-3 md:w-auto md:shrink-0 md:self-center">
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

            <x-products.visit-website-button :product="$product" surface="product_details" full-width class="min-w-0 flex-1 md:w-auto md:flex-none md:min-w-[140px]" />
        </div>
    </div>
</div>
