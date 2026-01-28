@extends('layouts.app')

@section('title')
    All Categories
@endsection

@section('actions')
@endsection

@section('content')
    <div class="p-4">
        @if($groupedCategories->every(fn($group) => $group->isEmpty()))
            <p class="text-gray-600">No categories are currently available.</p>
        @else
            @foreach($groupedCategories as $groupName => $categories)
                @if($categories->isNotEmpty())
                    <div class="mb-10">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4 pb-2">{{ $groupName }} Categories</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($categories as $category)
                                <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                                   class="flex items-center justify-between border bg-white px-3 py-1 hover:border-white text-gray-600 hover:text-white font-medium hover:bg-primary-500 rounded-md text-xs transition-all duration-200 ease-in-out">
                                    <span class="">{{ $category->name }}</span>
                                    <span class="ml-2 text-xs bg-gray-50 text-gray-500 rounded-lg px-2 py-0.5">{{ $category->products_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
@endsection