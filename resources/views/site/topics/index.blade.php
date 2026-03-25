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
                                <div class="flex items-center gap-1 border bg-white rounded-md text-xs transition-all duration-200 ease-in-out overflow-hidden">
                                    <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                                       class="flex-1 flex items-center justify-between px-3 py-1 hover:border-white text-gray-600 hover:text-white font-medium hover:bg-primary-500">
                                        <span>{{ $category->name }}</span>
                                        <span class="ml-2 text-xs bg-gray-50 text-gray-500 rounded-lg px-2 py-0.5">{{ $category->products_count }}</span>
                                    </a>
                                    @if($category->types->contains('name', 'Software'))
                                        <a href="{{ route('pseo.best', $category->slug) }}"
                                           class="px-2 py-1 text-gray-400 hover:text-primary-600 border-l border-gray-100 flex-shrink-0"
                                           title="Best {{ $category->name }} software">
                                            🏆
                                        </a>
                                    @endif
                                </div>

                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
@endsection