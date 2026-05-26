@php
    use App\Support\ProductLogo;

    $productLogo = ProductLogo::storedUrl($product);
@endphp

{{-- Logo and Name Row --}}
<div class="flex items-center flex-shrink-0">
    @if(isset($isAdminView) && $isAdminView)
        <div @click="editingLogo = true" class="cursor-pointer">
            <template x-if="!editingLogo">
                @if($product->logo)
                    <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                        alt="{{ $product->name }} logo" class="w-14 h-14 object-contain rounded-xl">
                @elseif($product->link)
                    <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}"
                        alt="{{ $product->name }} favicon" class="w-14 h-14 object-contain rounded-xl">
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
                alt="{{ $product->name }} logo" class="w-14 h-14 object-contain rounded-xl">
        @else
            <div class="flex w-14 h-14 rounded-xl bg-gray-100 text-gray-500 items-center justify-center text-lg font-semibold">
                {{ ProductLogo::initial($product) }}
            </div>
        @endif
    @endif

    <div class="ml-4 flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
        <div class="site-heading-text text-xl font-bold text-gray-900">
            {{ $product->name }}
        </div>

        @if(Auth::check() && Auth::user()->hasRole('admin') && !(isset($isAdminView) && $isAdminView))
            <a href="{{ route('admin.products.edit', $product) }}"
                class="text-sm font-medium text-primary-600 underline decoration-transparent underline-offset-4 transition hover:decoration-current focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-sm">
                Edit
            </a>
        @endif
    </div>
</div>

{{-- Tagline --}}
<div class="mt-2">
    <p class="site-body-text text-[13px] leading-snug text-gray-800">
        @if(isset($isAdminView) && $isAdminView)
            <span x-show="!editingProductPageTagline" @click="editingProductPageTagline = true"
                x-text="product_page_tagline"></span>
            <input x-show="editingProductPageTagline" x-model="product_page_tagline"
                @keydown.enter="updateProduct(); editingProductPageTagline = false"
                @keydown.escape="editingProductPageTagline = false" class="form-input">
        @else
            {{ $product->product_page_tagline }}
        @endif
    </p>
</div>

{{-- Tags --}}
<div class="mt-2 flex flex-wrap items-center">
    @php
        $generalCategories = $product->categories->filter(function ($cat) {
            return !$cat->types->contains('name', 'Pricing')
                && !$cat->types->contains('name', 'Best for')
                && !$cat->types->contains('name', 'Use Case')
                && !$cat->types->contains('name', 'Use Cases')
                && !$cat->types->contains('name', 'Platform');
        });
    @endphp
    @foreach($generalCategories as $category)
        <a href="{{ route('categories.show', ['category' => $category->slug]) }}" wire:navigate.hover
            class="text-[0.65rem] text-gray-500 hover:underline hover:text-primary-600 transition-colors">{{ $category->name }}</a>
        @if(!$loop->last)
            <span class="text-gray-300 mx-2">&middot;</span>
        @endif
    @endforeach
</div>

{{-- Primary Action --}}
<div class="mt-6 mb-6 flex items-center gap-3">
    <x-products.visit-website-button :product="$product" surface="product_details" full-width class="min-h-[48px] flex-1" />

    <div
        x-data="{ saved: {{ $isSavedByCurrentUser ? 'true' : 'false' }} }"
        @product-collections-synced.window="saved = $event.detail.isSaved"
        class="shrink-0"
    >
        <button
            type="button"
            @click="{{ Auth::check() ? "\$dispatch('open-modal', { name: 'product-save-modal' })" : "\$dispatch('open-modal', { name: 'login-required-modal' })" }}"
            class="inline-flex h-[48px] w-[48px] items-center justify-center rounded-md border transition-colors"
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
    </div>
</div>
