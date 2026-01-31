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
        @if($product->logo)
            <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                alt="{{ $product->name }} logo" class="w-14 h-14 object-contain rounded-xl">
        @elseif($product->link)
            <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}"
                alt="{{ $product->name }} favicon" class="w-14 h-14 object-contain rounded-xl">
        @endif
    @endif

    <h1 class="text-xl font-bold text-gray-900 ml-4">
        {{ $product->name }}
    </h1>
</div>

{{-- Tagline --}}
<div class="mt-3">
    <p class="text-gray-800 text-sm leading-snug">
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
<div class="flex flex-wrap items-center mt-2.5">
    @php
        $generalCategories = $product->categories->filter(function ($cat) {
            return !$cat->types->contains('name', 'Pricing') && !$cat->types->contains('name', 'Best for');
        });
    @endphp
    @foreach($generalCategories as $category)
        <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
            class="text-[0.65rem] text-gray-500 hover:underline hover:text-primary-600 transition-colors">{{ $category->name }}</a>
        @if(!$loop->last)
            <span class="text-gray-300 mx-2">&middot;</span>
        @endif
    @endforeach
</div>

{{-- Interaction Buttons --}}
<div class="flex flex-row items-center mt-6 gap-3">
    <a href="{{ $product->link . (strpos($product->link, '?') === false ? '?' : '&') }}utm_source=softwareontheweb.com"
        target="_blank" rel="noopener ugc noreferrer"
        class="flex-1 inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
        Visit Website &nbsp;
        <svg class="size-4 stroke-gray-600" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
                <path d="M7 17L17 7M17 7H8M17 7V16" stroke="" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round"></path>
            </g>
        </svg>
    </a>
    @unless(isset($isAdminView) && $isAdminView)
        <div class="flex-1">
            @livewire('product-upvote-button', ['product' => $product])
        </div>
    @endunless
</div>