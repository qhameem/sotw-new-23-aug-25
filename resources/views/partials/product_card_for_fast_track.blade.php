@php use Illuminate\Support\Str; @endphp
@php
    $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
    $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
@endphp
<article class="p-4 flex items-center gap-3 md:gap-3 transition relative group cursor-pointer hover:bg-gray-50 border-b"
         @click="if (!$event.target.closest('input, a')) { $dispatch('open-product-modal', {{ json_encode($alpineProducts->firstWhere('id', $product->id)) }}) }">
    <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo" class="w-[65px] h-[65px] rounded-lg object-cover flex-shrink-0" loading="lazy" />
    <div class="flex-1">
        <h2 class="text-sm font-semibold leading-tight mb-0.5 flex items-center">
            <span class="text-left">{{ $product->name }}</span>
        </h2>
        <p class="text-gray-800 text-sm md:text-sm mt-0.5 line-clamp-2">{{ $product->tagline }}</p>
        
        <div class="mt-2">
            <x-scheduled-datepicker name="publish_date[{{ $product->id }}]" value="{{ now()->toDateString() }}" class="product-date" />
        </div>
    </div>

    <div class="flex flex-col items-center justify-center ml-1 md:ml-2 w-24 flex-shrink-0">
        <input type="checkbox" class="product-checkbox h-5 w-5 text-primary-500 border-gray-300 rounded focus:ring-primary-500" value="{{ $product->id }}" data-price="30">
        <label class="mt-1 text-sm font-bold text-gray-700">$30</label>
    </div>
</article>