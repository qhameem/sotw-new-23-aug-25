@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-bold pt-14 mb-6">Edit Type</h1>
    <form action="{{ route('admin.category-types.update', $type) }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block font-semibold mb-1">Type Name</label>
            <input type="text" name="name" class="w-full border rounded px-3 py-2   " required
                value="{{ old('name', $type->name) }}">
            @error('name')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Description</label>
            <textarea name="description" class="w-full border rounded px-3 py-2   " rows="3">{{ old('description', $type->description) }}</textarea>
            @error('description')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="mb-4">
            <label class="block font-semibold mb-1">Assign to Categories (Optional)</label>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 border rounded p-3 ">
                @foreach($categories as $cat)
                <label class="inline-flex items-center text-sm">
                    <input type="checkbox" name="categories[]" value="{{ $cat->id }}"
                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50    mr-1"
                           {{ (is_array(old('categories')) && in_array($cat->id, old('categories'))) || (!old('categories') && in_array($cat->id, $assigned)) ? 'checked' : '' }}>
                    <span>{{ $cat->name }}</span>
                </label>
                @endforeach
            </div>
            @error('categories')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="flex justify-end">
            <a href="{{ route('admin.category-types.index') }}" class="mr-4 text-gray-600  hover:underline">Cancel</a>
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-primary-600 focus:bg-primary-600 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    style="color: var(--color-primary-button-text);">Save</button>
        </div>
    </form>
</div>
@endsection