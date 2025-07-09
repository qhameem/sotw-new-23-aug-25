@props(['product'])

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="p-4">
        <div class="flex items-start justify-between">
            <div class="flex items-start">
                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox rounded mt-1">
                <div class="ml-4">
                    <a href="{{ route('admin.products.show', $product) }}" class="text-lg font-semibold text-blue-600 hover:underline">{{ $product->name }}</a>
                    <p class="text-sm text-gray-500">Submitted by: {{ $product->user->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="flex-shrink-0">
                @if($product->logo)
                    <img src="{{ $product->logo_url }}" alt="{{ $product->name }} logo" class="h-12 w-12 rounded-full object-cover">
                @elseif($product->link)
                    <img src="https://www.google.com/s2/favicons?sz=64&domain_url={{ urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="h-12 w-12 rounded-full object-cover">
                @else
                    <div class="h-12 w-12 rounded-full bg-gray-200"></div>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <p class="text-sm text-gray-600">
                <strong>Status:</strong>
                @if($product->approved)
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        Approved
                    </span>
                @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Pending
                    </span>
                @endif
            </p>
            <p class="text-sm text-gray-600 mt-2">
                <strong>URL:</strong>
                <a href="{{ $product->link }}" target="_blank" class="text-blue-600 hover:underline">{{ Str::limit($product->link, 30) }}</a>
            </p>
        </div>

        <div class="mt-4 pt-4 border-t">
            <form action="{{ route('admin.products.updatePromotion', $product) }}" method="POST" class="flex items-center justify-between">
                @csrf
                <div>
                    <label for="is_promoted_{{ $product->id }}" class="flex items-center cursor-pointer">
                        <input type="checkbox" id="is_promoted_{{ $product->id }}" name="is_promoted" value="1" @if($product->is_promoted) checked @endif class="form-checkbox h-5 w-5 text-primary-600">
                        <span class="ml-2 text-sm text-gray-700">Promote</span>
                    </label>
                </div>
                <div class="flex items-center">
                    <input type="number" name="promoted_position" value="{{ $product->promoted_position }}" class="w-20 p-1 border rounded text-sm" placeholder="Pos.">
                    <button type="submit" class="ml-2 text-xs bg-blue-500 hover:bg-blue-700 text-white py-1 px-2 rounded">Update</button>
                </div>
            </form>
        </div>
    </div>
    <div class="bg-gray-50 px-4 py-3 flex items-center justify-end space-x-2">
        <a href="{{ route('admin.products.edit', $product) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Edit</a>
        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this product?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-600 hover:text-red-900">Delete</button>
        </form>
    </div>
</div>