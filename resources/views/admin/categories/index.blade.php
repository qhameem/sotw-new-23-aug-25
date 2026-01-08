@extends('layouts.app')

@section('header-title')
    Categories
@endsection

@section('actions')
    <a href="{{ route('admin.category-types.create') }}"
       class="inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-primary-600 focus:bg-primary-600 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
       style="color: var(--color-primary-button-text);">Add New Category Type</a>
    <a href="{{ route('admin.categories.create') }}"
       class="inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-primary-600 focus:bg-primary-600 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
       style="color: var(--color-primary-button-text);">Create Category</a>
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div>
        {{-- Session Messages --}}
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
        @endif

        {{-- Category Types Section --}}
        <div class="mb-12">
            <h2 class="text-xl font-bold mb-4">Category Types</h2>
            <div class="bg-white  shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full bg-white  rounded">
                    <thead>
                        <tr class="bg-gray-50 ">
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Name</th>
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Description</th>
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 ">
                        @forelse($categoryTypes as $type)
                        <tr>
                            <td class="py-2 px-4 border-b  text-sm text-gray-900 ">{{ $type->name }}</td>
                            <td class="py-2 px-4 border-b  text-sm text-gray-700 ">{{ Str::limit($type->description, 50) }}</td>
                            <td class="py-2 px-4 border-b  text-sm">
                                <a href="{{ route('admin.category-types.edit', $type) }}"
                                   class="text-primary-600  hover:text-primary-900  mr-2">Edit</a>
                                <form action="{{ route('admin.category-types.destroy', $type) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600  hover:text-red-900 "
                                            onclick="return confirm('Are you sure you want to delete this type? This will also detach it from all categories.')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-4 px-4 text-center text-sm text-gray-500 ">No category types found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Categories Section --}}
        <div x-data="{ searchTerm: '', selectedType: '{{ $selectedType }}', sortByType: '{{ $sortByType }}', categories: {{ Js::from($categories) }} }">
            <h2 class="text-xl font-bold mb-4">Categories</h2>

            <!-- Filter and Sort Controls -->
            <div class="flex flex-wrap gap-4 mb-4">
                <!-- Type Filter -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Type</label>
                    <select x-model="selectedType"
                            class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                            @change="window.location = '?type=' + selectedType + '&sort_by_type=' + sortByType">
                        <option value="">All Types</option>
                        @foreach($categoryTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort by Type -->
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort by Type</label>
                    <select x-model="sortByType"
                            class="w-full px-4 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                            @change="window.location = '?type=' + selectedType + '&sort_by_type=' + sortByType">
                        <option value="">None</option>
                        <option value="asc">Type A-Z</option>
                        <option value="desc">Type Z-A</option>
                    </select>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="mb-4">
                <input type="text" x-model="searchTerm" placeholder="Search categories by name or description..."
                       class="w-full px-4 py-2 text-sm placeholder-gray-500 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500     ">
            </div>

            <div class="bg-white  shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full bg-white  rounded">
                    <thead>
                        <tr class="bg-gray-50 ">
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Name</th>
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Slug</th>
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Description</th>
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Types</th>
                            <th class="py-2 px-4 border-b  text-left text-xs font-medium text-gray-500  uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 ">
                        <template x-for="category in categories.filter(cat =>
                            cat.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            (cat.description && cat.description.toLowerCase().includes(searchTerm.toLowerCase()))
                        )" :key="category.id">
                            <tr>
                                <td class="py-2 px-4 border-b  text-xs text-gray-900 " x-text="category.name"></td>
                                <td class="py-2 px-4 border-b  text-xs text-gray-700 " x-text="category.slug"></td>
                                <td class="py-2 px-4 border-b  text-xs text-gray-700 " x-text="category.description ? category.description.substring(0, 50) + (category.description.length > 50 ? '...' : '') : ''"></td>
                                <td class="py-2 px-4 border-b  text-xs text-gray-700 ">
                                    <template x-for="type in category.types" :key="type.id">
                                        <span class="inline-block bg-gray-200  text-gray-700  px-2 py-0.5 text-xs rounded-full mr-1 mb-1" x-text="type.name"></span>
                                    </template>
                                </td>
                                <td class="py-2 px-4 border-b  text-xs">
                                    <a :href="`{{ url('admin/categories') }}/${category.id}/edit`"
                                       class="text-primary-600  hover:text-primary-900  mr-2">Edit</a>
                                    <form :action="`{{ url('admin/categories') }}/${category.id}`" method="POST" class="inline" @submit.prevent="if(confirm('Are you sure you want to delete this category?')) $el.submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600  hover:text-red-900 ">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        </template>
                        <template x-if="categories.filter(cat => cat.name.toLowerCase().includes(searchTerm.toLowerCase()) || (cat.description && cat.description.toLowerCase().includes(searchTerm.toLowerCase()))).length === 0">
                            <tr>
                                <td colspan="5" class="py-4 px-4 text-center text-xs text-gray-500 ">No categories found matching your search.</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
           </div>
        </div>
    </div>
@endsection