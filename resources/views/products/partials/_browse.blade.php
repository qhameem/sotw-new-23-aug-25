@php
    $groupLabels = [
        'Software Categories' => 'Categories',
        'Software' => 'Categories',
        'Category' => 'Categories',
        'Use Case' => 'Use Cases',
        'Use Cases' => 'Use Cases',
        'Best for' => 'Best For',
        'Platform' => 'Platforms',
        'Pricing' => 'Pricing',
    ];
    $hiddenGroupTypes = ['Software Categories', 'Software', 'Category'];
    $isCatalog = request()->routeIs('products.catalog');
    $activeCategoryId = isset($category) ? (int) $category->id : null;
@endphp

<nav class="space-y-5" aria-label="Browse software">
    <div class="flex items-center gap-[2px]">
        <span class="flex h-8 w-8 items-center justify-center text-primary-600">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M9.73319 10.4608C9.82276 10.1025 10.1025 9.82273 10.4608 9.73315L13.9187 8.86869C14.651 8.68559 15.3144 9.34899 15.1313 10.0814L14.2669 13.5392C14.1773 13.8975 13.8975 14.1773 13.5393 14.2668L10.0814 15.1313C9.34902 15.3144 8.68562 14.651 8.86872 13.9186L9.73319 10.4608Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M12 3.5C5.5 3.5 3.5 5.5 3.5 12C3.5 18.5 5.5 20.5 12 20.5C18.5 20.5 20.5 18.5 20.5 12C20.5 5.5 18.5 3.5 12 3.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <p class="text-sm font-semibold text-gray-900">Browse</p>
    </div>

    <a href="{{ route('stats') }}" wire:navigate.hover @if(request()->routeIs('stats')) aria-current="page" @endif
        @class(['block rounded-xl px-4 py-1.5 text-sm', 'bg-purple-50 font-semibold text-purple-600' => request()->routeIs('stats'), 'text-gray-600 hover:bg-gray-50 hover:text-purple-600' => !request()->routeIs('stats')])>
        Stats
    </a>

    <a href="{{ route('products.catalog') }}" wire:navigate.hover
        @if(request()->routeIs('products.catalog') && empty($filters)) aria-current="page" @endif
        @class(['block rounded-xl px-4 py-1.5 text-sm', 'bg-purple-50 font-semibold text-purple-600' => request()->routeIs('products.catalog') && empty($filters), 'text-gray-600 hover:bg-gray-50 hover:text-purple-600' => !request()->routeIs('products.catalog') || !empty($filters)])>
        All products
    </a>

    @foreach(($types ?? collect()) as $type)
        @continue(in_array($type->name, $hiddenGroupTypes, true))
        @php $options = $type->categories->filter(fn ($item) => ($item->products_count ?? 0) > 0); @endphp
        @if($options->isNotEmpty())
            <section>
                <div class="mb-2 flex items-center justify-between border-l-2 border-purple-200 pl-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-purple-600">
                        {{ $groupLabels[$type->name] ?? $type->name }}
                    </p>
                    <a href="{{ route('categories.index') }}" wire:navigate.hover class="text-[11px] font-medium text-gray-400 hover:text-purple-600">
                        See all <span aria-hidden="true">›</span>
                    </a>
                </div>

                <div class="space-y-0.5">
                    @php
                        $filterKey = collect($taxonomies ?? [])->search(fn ($names) => in_array($type->name, $names, true)) ?: 'category';
                    @endphp
                    @foreach($options->take(5) as $item)
                        @php
                            $isActive = $isCatalog
                                ? ($filters[$filterKey] ?? null) === $item->slug
                                : $activeCategoryId === (int) $item->id;
                        @endphp
                        <a href="{{ $isCatalog ? route('products.catalog', [$filterKey => $item->slug] + $catalogParameters) : $item->publicUrl() }}" wire:navigate.hover
                            @class([
                                'block rounded-xl px-4 py-1.5 text-xs transition',
                                'bg-purple-50 font-semibold text-purple-600' => $isActive,
                                'text-gray-600 hover:bg-gray-50 hover:text-purple-600' => !$isActive,
                            ])>
                            {{ $item->name }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</nav>
