@php
    use App\Support\ProductLogo;

    $productLogo = ProductLogo::storedUrl($product);
@endphp

<div class="mb-6 flex flex-row items-start gap-5">
    {{-- Logo --}}
    <div class="flex-shrink-0">
        @if(isset($isAdminView) && $isAdminView)
            <div @click="editingLogo = true" class="cursor-pointer">
                <template x-if="!editingLogo">
                    @if($product->logo)
                        <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                            alt="{{ $product->name }} logo" class="w-[100px] h-[100px] object-contain rounded-xl">
                    @elseif($product->link)
                        <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}"
                            alt="{{ $product->name }} favicon" class="w-[100px] h-[100px] object-contain rounded-xl">
                    @endif
                </template>
                <template x-if="editingLogo">
                    <div class="mt-2">
                        <input type="file" class="text-xs">
                        <div class="mt-1 flex gap-2">
                            <button @click.stop="updateProduct(); editingLogo = false"
                                class="text-[10px] bg-primary-500 text-white px-2 py-1 rounded">Save</button>
                            <button @click.stop="editingLogo = false"
                                class="text-[10px] bg-gray-200 px-2 py-1 rounded">Cancel</button>
                        </div>
                    </div>
                </template>
            </div>
        @else
            @if($productLogo)
                <img src="{{ $productLogo }}"
                    alt="{{ $product->name }} logo" class="w-[100px] h-[100px] object-contain rounded-xl">
            @else
                <div class="flex w-[100px] h-[100px] rounded-xl bg-gray-100 text-gray-500 items-center justify-center text-3xl font-semibold">
                    {{ ProductLogo::initial($product) }}
                </div>
            @endif
        @endif
    </div>

    {{-- Content Area --}}
    <div class="flex-1">
        <div class="flex items-start justify-between gap-6">
            <div class="min-w-0 max-w-4xl flex-1">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <div class="site-heading-text text-2xl font-bold text-gray-900">
                        @if(isset($isAdminView) && $isAdminView)
                            <span x-show="!editingName" @click="editingName = true" x-text="name"></span>
                            <input x-show="editingName" x-model="name" @keydown.enter="updateProduct(); editingName = false"
                                @keydown.escape="editingName = false" class="form-input">
                        @else
                            {{ $product->name }}
                        @endif
                    </div>

                    @if(Auth::check() && Auth::user()->hasRole('admin') && !(isset($isAdminView) && $isAdminView))
                        <a href="{{ route('admin.products.edit', $product) }}"
                            class="text-sm font-medium text-primary-600 underline decoration-transparent underline-offset-4 transition hover:decoration-current focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-sm">
                            Edit
                        </a>
                    @endif
                </div>

                <div class="mt-1 min-w-0 max-w-4xl">
                    {{-- Tagline --}}
                    <p class="site-body-text text-[15px] leading-snug text-gray-800">
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

                {{-- Tags --}}
                <div class="mt-1 flex min-w-0 flex-wrap items-center">
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
                        <svg class="mr-2 h-3.5 w-3.5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M7.25 4.75h-2.5A1.5 1.5 0 0 0 3.25 6.25v2.5a1.5 1.5 0 0 0 .44 1.06l6.5 6.5a1.5 1.5 0 0 0 2.12 0l4-4a1.5 1.5 0 0 0 0-2.12l-6.5-6.5a1.5 1.5 0 0 0-1.06-.44Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M6.5 7.25a.375.375 0 1 0 0-.75.375.375 0 0 0 0 .75Z" fill="currentColor" stroke="currentColor" />
                        </svg>
                    @endif
                    @foreach($generalCategories as $category)
                        <a href="{{ route('categories.show', ['category' => $category->slug]) }}" wire:navigate.hover
                            class="text-xs text-gray-500 transition-colors hover:text-primary-600 hover:underline">{{ $category->name }}</a>
                        @if(!$loop->last)
                            <span class="mx-2 text-gray-300">&middot;</span>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex shrink-0 self-center items-center gap-3">
                <x-products.visit-website-button :product="$product" surface="product_details" label="Visit" content-class="" class="font-bold" />

                <div
                    x-data="{ saved: {{ $isSavedByCurrentUser ? 'true' : 'false' }} }"
                    @product-collections-synced.window="saved = $event.detail.isSaved"
                    class="group relative"
                >
                    <button
                        type="button"
                        @click="{{ Auth::check() ? "\$dispatch('open-modal', { name: 'product-save-modal' })" : "\$dispatch('open-modal', { name: 'login-required-modal' })" }}"
                        class="group inline-flex w-[42px] items-center justify-center rounded-md border px-3 py-1.5 text-sm leading-5 transition-colors"
                        :class="saved ? 'border-gray-300 bg-gray-100 text-gray-500 hover:bg-gray-200' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
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
    </div>
</div>
