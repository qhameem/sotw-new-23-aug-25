@php
    use App\Support\ProductLogo;

    $productLogo = ProductLogo::storedUrl($product);
@endphp

<div class="flex flex-row items-start mb-6">
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
    <div class="ml-5 flex-1">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1 max-w-4xl">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h1 class="site-heading-text text-2xl font-bold text-gray-900">
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
                            class="text-sm font-medium text-primary-600 underline decoration-transparent underline-offset-4 transition hover:decoration-current focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-sm">
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-2 flex items-start justify-between gap-6">
            <div class="min-w-0 flex-1 max-w-4xl">
                {{-- Tagline --}}
                <p class="site-body-text text-gray-800 text-base leading-snug">
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

            <div class="shrink-0">
                <x-products.visit-website-button :product="$product" surface="product_details" class="min-h-7 whitespace-nowrap text-base font-semibold" />
            </div>
        </div>

        {{-- Tags --}}
        <div class="flex flex-wrap items-center mt-2.5">
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
                <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                    class="text-xs text-gray-500 hover:underline hover:text-primary-600 transition-colors">{{ $category->name }}</a>
                @if(!$loop->last)
                    <span class="text-gray-300 mx-2">&middot;</span>
                @endif
            @endforeach
        </div>
    </div>
</div>
