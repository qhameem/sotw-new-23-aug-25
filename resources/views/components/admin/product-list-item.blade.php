@props(['product'])

<article class="p-4 flex items-center gap-3 md:gap-3 transition relative group">
    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox rounded">
    <img src="{{ $product->logo_url ?? 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} logo" class="w-[65px] h-[65px] rounded-lg object-cover flex-shrink-0" loading="lazy" />
    <div class="flex-1">
        <h2 class="text-sm font-semibold leading-tight mb-0.5 flex items-center">
            <a href="{{ route('admin.products.show', $product) }}" class="text-left text-blue-600 hover:underline">{{ $product->name }}</a>
            <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow" class="ml-2 p-1 opacity-50 group-hover:opacity-100 transition-opacity duration-200 rounded-full hover:bg-gray-100" aria-label="Open product link in new tab">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </h2>
        <p class="text-gray-800 text-sm mt-0.5 line-clamp-2">{{ $product->tagline }}</p>
        <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
            <span>Submitted by: {{ $product->user->name ?? 'N/A' }}</span>
            <span>
                Status:
                @if($product->approved)
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        Approved
                    </span>
                @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Pending
                    </span>
                @endif
            </span>
        </div>
    </div>
    <div class="flex flex-col items-end space-y-2">
        <form action="{{ route('admin.products.updatePromotion', $product) }}" method="POST" class="flex items-center gap-2">
            @csrf
            <label for="is_promoted_{{ $product->id }}" class="flex items-center cursor-pointer">
                <input type="checkbox" id="is_promoted_{{ $product->id }}" name="is_promoted" value="1" @if($product->is_promoted) checked @endif class="form-checkbox h-4 w-4 text-primary-600">
                <span class="ml-2 text-xs text-gray-700">Promote</span>
            </label>
            <input type="number" name="promoted_position" value="{{ $product->promoted_position }}" class="w-16 p-1 border rounded text-xs" placeholder="Pos.">
            <button type="submit" class="text-xs bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded">Update</button>
        </form>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.edit', $product) }}" class="text-xs text-indigo-600 hover:text-indigo-900">Edit</a>
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-600 hover:text-red-900">Delete</button>
            </form>
        </div>
    </div>
</article>