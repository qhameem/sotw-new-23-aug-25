@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-2xl font-bold pt-14 mb-4">Edit Category</h1> {{-- Added pt-14 for consistency --}}
    @if($errors->any())
        <div class="bg-red-100 text-red-800 p-2 rounded mb-4">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Name</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" class="w-full border rounded px-3 py-2   " required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="w-full border rounded px-3 py-2   " required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Description</label>
            <textarea name="description" class="w-full border rounded px-3 py-2   ">{{ old('description', $category->description) }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Meta Description</label>
            <textarea name="meta_description" class="w-full border rounded px-3 py-2   ">{{ old('meta_description', $category->meta_description) }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block mb-1 font-semibold">Category Types</label>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 border rounded p-3 ">
                @forelse($categoryTypes as $type)
                    <label class="flex items-center space-x-2 text-sm">
                        <input type="checkbox" name="category_types[]" value="{{ $type->id }}"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50   "
                               @if( (is_array(old('category_types')) && in_array($type->id, old('category_types'))) || (!old('category_types') && in_array($type->id, $assignedTypeIds)) ) checked @endif>
                        <span>{{ $type->name }}</span>
                    </label>
                @empty
                    <p class="text-gray-500  col-span-full">No category types available.</p>
                @endforelse
            </div>
        </div>

        <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-primary-600 focus:bg-primary-600 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                style="color: var(--color-primary-button-text);">Update</button>
        <a href="{{ route('admin.categories.index') }}" class="ml-2 text-gray-600  hover:underline">Cancel</a>
    </form>
</div>
@endsection 