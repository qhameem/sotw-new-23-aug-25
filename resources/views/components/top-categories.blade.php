<div class="p-4">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-base font-semibold text-gray-700">Top Categories</h3>
        <a href="{{ route('categories.index') }}" class="text-xs text-gray-600 hover:underline">View all</a>
    </div>
    @php
        $topCategories = \App\Models\Category::withCount('products')
            ->whereHas('types', function ($query) {
                $query->where('name', '!=', 'pricing');
            })
            ->orderBy('products_count', 'desc')
            ->take(7)
            ->get();
    @endphp
    <ul class="space-y-2">
        @foreach($topCategories as $category)
            <li>
                <a href="{{ route('categories.show', $category->slug) }}" class="flex justify-between items-center">
                    <span class="text-gray-700 text-sm">{{ $category->name }}</span>
                    <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-full">{{ $category->products_count }} products</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>