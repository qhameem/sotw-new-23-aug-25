@props(['categories', 'selected' => [], 'type'])

<div>
    <label class="block text-xs font-semibold md:text-left md:pr-4 mb-1">Best for<span class="text-red-500 ml-1">*</span></label>
    <select multiple name="categories[]" class="form-multiselect block w-full mt-1">
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @if(in_array($category->id, $selected)) selected @endif>{{ $category->name }}</option>
        @endforeach
    </select>
</div>