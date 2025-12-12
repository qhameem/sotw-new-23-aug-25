@props(['categories', 'selected' => [], 'type'])

<div>
    <div class="flex justify-between items-center">
        <label class="block text-xs font-semibold md:text-left md:pr-4 mb-1">Pricing Model<span class="text-red-500 ml-1">*</span></label>
        <span class="text-xs text-gray-600">Select at least 1</span>
    </div>
    <div class="grid grid-cols-2 gap-y-1 border rounded-md p-3">
        @forelse($categories as $category)
            <label class="flex items-center py-1 text-xs cursor-pointer hover:bg-gray-50 px-1 rounded">
                <input type="checkbox" name="categories[]" value="{{ $category->id }}" @if(in_array($category->id, $selected)) checked @endif class="mr-2 rounded border-gray-300 text-primary-600 shadow-sm">
                <span class="text-gray-700">{{ $category->name }}</span>
            </label>
        @empty
            <p class="col-span-full text-center text-xs text-gray-500 py-2">No pricing categories available.</p>
        @endforelse
    </div>
</div>