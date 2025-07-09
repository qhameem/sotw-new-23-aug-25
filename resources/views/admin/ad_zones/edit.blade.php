@extends('layouts.app')

@section('title', 'Edit Ad Zone')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Edit Ad Zone: {{ $adZone->name }}</h1>

    <form action="{{ route('admin.ad-zones.update', $adZone) }}" method="POST" class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-8 pt-6 pb-8 mb-4">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="name">
                Name
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror" id="name" name="name" type="text" placeholder="e.g., Header Banner" value="{{ old('name', $adZone->name) }}" required>
            @error('name')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="slug">
                Slug
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('slug') border-red-500 @enderror" id="slug" name="slug" type="text" placeholder="e.g., header-banner" value="{{ old('slug', $adZone->slug) }}" required>
            @error('slug')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="description">
                Description
            </label>
            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('description') border-red-500 @enderror" id="description" name="description" placeholder="Optional description for this ad zone">{{ old('description', $adZone->description) }}</textarea>
            @error('description')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="display_after_nth_product">
                Display After Nth Product (for list-based zones)
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('display_after_nth_product') border-red-500 @enderror" id="display_after_nth_product" name="display_after_nth_product" type="number" min="1" placeholder="e.g., 3 (to display after 3rd product)" value="{{ old('display_after_nth_product', $adZone->display_after_nth_product) }}">
            <p class="text-xs text-gray-500 mt-1">Only applicable for zones like 'Below Product Listing'. Leave empty if not applicable.</p>
            @error('display_after_nth_product')
                <p class="text-red-500 text-xs italic">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Update Ad Zone
            </button>
            <a href="{{ route('admin.ad-zones.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-600">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection