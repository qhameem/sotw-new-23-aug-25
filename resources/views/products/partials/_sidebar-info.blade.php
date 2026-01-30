<div class="space-y-6">
    @unless($product->user->hasRole('admin'))
        <div>
            <h3 class="text-[0.65rem] font-semibold text-gray-400 uppercase tracking-tight mb-2">Publisher</h3>
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
            <h3 class="text-[0.65rem] font-semibold text-gray-400 uppercase tracking-tight mb-2">Best for</h3>
            <div class="flex flex-wrap gap-1.5">
                @foreach($bestForCategories as $category)
                    <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                        class="text-[0.7rem] text-gray-700 hover:text-gray-900 font-medium">
                        {{ $category->name }}@if(!$loop->last)<span class="text-gray-300 ml-1.5">•</span>@endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($pricingCategory)
        <div>
            <h3 class="text-[0.65rem] font-semibold text-gray-400 uppercase tracking-tight mb-1">Pricing Model</h3>
            <p class="text-[0.7rem] text-gray-700 font-medium">{{ $pricingCategory->name }}</p>
        </div>
    @endif

    @if($product->techStacks->isNotEmpty())
        <div>
            <h3 class="text-sm font-semibold text-gray-800 mb-2">Tech Stack</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($product->techStacks as $techStack)
                    <span
                        class="inline-flex items-center px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                        {{ $techStack->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

</div>