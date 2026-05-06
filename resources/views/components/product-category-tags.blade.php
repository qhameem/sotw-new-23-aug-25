@props([
    'categories',
    'withCounts' => false,
    'size' => 'sm', // sm, md, lg - for text size
    'hideOnMobile' => true, // whether to hide on mobile screens
])

@php
    $sizeClasses = match($size) {
        'lg' => 'text-sm',
        'md' => 'text-[0.65rem]',
        'sm' => 'text-xs',
        default => 'text-[0.65rem]',
    };
@endphp

<div class="flex flex-wrap gap-2 items-center">
    @php
        $generalCategories = $categories->filter(function ($cat) {
            return !$cat->types->contains('name', 'Pricing') && !$cat->types->contains('name', 'Best for');
        });
    @endphp
    @forelse($generalCategories as $category)
        <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
           @click.stop
           class="@if($hideOnMobile) hidden sm:inline-flex @else inline-flex @endif items-center rounded text-gray-600 hover:text-gray-800 {{ $sizeClasses }}">
            <span class="hover:underline">{{ $category->name }}</span>
            
            @if($withCounts && isset($category->products_count))
                <span class="ml-1.5 mr-2 h-5 w-5 min-w-[1.25rem] rounded-full text-gray-500 hover:text-gray-600 {{ $sizeClasses }} font-semibold flex items-center justify-center leading-none antialiased">
                    {{ $category->products_count > 99 ? '99+' : $category->products_count }}
                </span>
            @endif
        </a>
        
        @if(!$loop->last)
            <span class="@if($hideOnMobile) hidden sm:inline @else inline @endif text-gray-400">•</span>
        @endif
    @empty
        <!-- No categories to display -->
    @endforelse
</div>
