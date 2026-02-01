@php
    $sidebarSnippets = \App\Models\CodeSnippet::where('location', 'sidebar')->get();
    $page = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp
<div class="space-y-6">
    <div class="sidebar-snippets-container">
        @foreach ($sidebarSnippets as $snippet)
            @if ($snippet->page === 'all' || request()->routeIs(str_replace('.index', '.*', $snippet->page)))
                {!! html_entity_decode($snippet->code) !!}
            @endif
        @endforeach
    </div>
    @unless($product->user->hasRole('admin'))
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Publisher</h3>
            <div class="flex items-center gap-2">
                <img src="{{ $product->user->avatar() }}" alt="{{ $product->user->name }}"
                    class="size-6 rounded-full border border-gray-100">
                <div class="text-gray-800 text-sm font-medium">
                    {{ $product->user->name }}
                </div>
            </div>
        </div>
    @endunless

    @if($bestForCategories->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">This product is best for</h3>
            <div class="flex flex-wrap gap-1.5">
                @foreach($bestForCategories as $category)
                    <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                        class="text-xs text-gray-700 hover:text-gray-900 font-medium">
                        {{ $category->name }}@if(!$loop->last)<span class="text-gray-300 ml-1.5">•</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($pricingCategory)
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Pricing Model</h3>
            <p class="text-xs text-gray-700 font-medium">{{ $pricingCategory->name }}</p>
        </div>
    @endif

    @if($product->techStacks->isNotEmpty())
        <div>
            <h3 class="text-xs text-gray-500 mb-2">Tech Stack</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($product->techStacks as $techStack)
                    <span class="inline-flex items-center text-xs text-gray-700 font-medium">
                        {{ $techStack->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

</div>