@php
    $topCategories = cache()->remember('top_categories_sidebar', config('performance.top_categories_cache_ttl', 3600), function () {
        return \App\Models\Category::whereDoesntHave('types', function ($query) {
            $query->where('types.id', 2);
        })
            ->withCount([
                'products' => function ($query) {
                    $query->where('approved', true)
                        ->where('is_published', true);
                }
            ])
            ->orderBy('products_count', 'desc')
            ->orderBy('name')
            ->take(config('performance.max_top_categories_display', 6))
            ->get();
    });
@endphp

<div class="p-4">
    <h3 class="text-sm font-semibold mb-4 text-gray-800">Top Categories</h3>
    <ul class="space-y-2">
        @forelse($topCategories as $category)
            <li>
                <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                    class="flex justify-between items-center text-xs text-gray-700 hover:underline">
                    <span>{{ $category->name }}</span>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                        {{ $category->products_count }} products
                    </span>
                </a>
            </li>
        @empty
            <li class="text-sm text-gray-500">No categories available</li>
        @endforelse
    </ul>
</div>