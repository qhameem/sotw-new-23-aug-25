<section class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('My Collections') }}
            </h2>

            <p class="mt-1 max-w-2xl text-sm text-gray-600">
                {{ __('Organize saved products into public or private lists without crowding your profile settings page.') }}
            </p>
        </div>

        <a href="{{ route('collections.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
            Open Collections
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Collections</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $productCollectionsCount ?? 0 }}</p>
            <p class="mt-1 text-sm text-gray-600">Lists you can keep public or private.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Saved Products</p>
            <p class="mt-3 text-3xl font-semibold text-gray-900">{{ $savedProductsCount ?? 0 }}</p>
            <p class="mt-1 text-sm text-gray-600">Total items across all of your collections.</p>
        </div>

        <div class="rounded-xl border border-dashed border-gray-300 p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Starter Ideas</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach(($defaultProductCollectionNames ?? []) as $defaultCollectionName)
                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                        {{ $defaultCollectionName }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-4">
            <h3 class="text-sm font-semibold text-gray-900">Recent Collections</h3>
            <a href="{{ route('collections.index') }}" class="text-sm font-medium text-primary-600 hover:underline">
                Manage all
            </a>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse(($recentProductCollections ?? collect()) as $collection)
                <a href="{{ route('collections.show', $collection->publicRouteParameters()) }}" class="flex items-center justify-between gap-4 px-4 py-4 transition hover:bg-gray-50">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-sm font-semibold text-gray-900">{{ $collection->name }}</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $collection->visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($collection->visibility) }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ trans_choice('{0} No products yet|{1} 1 saved product|[2,*] :count saved products', $collection->items_count, ['count' => $collection->items_count]) }}
                        </p>
                    </div>

                    <span class="text-sm font-medium text-gray-400">View</span>
                </a>
            @empty
                <div class="px-4 py-6 text-sm text-gray-600">
                    No collections yet. Create your first one from the dedicated collections page.
                </div>
            @endforelse
        </div>
    </div>
</section>
