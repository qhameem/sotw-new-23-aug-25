<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('My Collections') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Create public or private lists for the products you want to keep track of.') }}
        </p>
    </header>

    @if(session('status') === 'collection-created')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Collection created.
        </div>
    @elseif(session('status') === 'collection-updated')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Collection updated.
        </div>
    @elseif(session('status') === 'collection-deleted')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Collection deleted.
        </div>
    @endif

    <form method="POST" action="{{ route('collections.store') }}" class="grid gap-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-[minmax(0,1fr)_180px_auto] md:items-end">
        @csrf

        <div>
            <x-input-label for="collection_name" :value="__('New collection name')" />
            <x-text-input id="collection_name" name="name" type="text" class="mt-1 block w-full" maxlength="120" placeholder="Favorites" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
            @if(($productCollections ?? collect())->isEmpty())
                <p class="mt-2 text-xs text-gray-500">
                    Suggested starters:
                    {{ collect($defaultProductCollectionNames ?? [])->implode(', ') }}
                </p>
            @endif
        </div>

        <div>
            <x-input-label for="collection_visibility" :value="__('Visibility')" />
            <select id="collection_visibility" name="visibility" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('visibility')" />
        </div>

        <div>
            <x-primary-button>{{ __('Create Collection') }}</x-primary-button>
        </div>
    </form>

    <div class="space-y-4">
        @forelse(($productCollections ?? collect()) as $collection)
            <div class="rounded-2xl border border-gray-200 p-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('collections.show', $collection->publicRouteParameters()) }}" class="text-base font-semibold text-gray-900 hover:text-primary-600">
                                {{ $collection->name }}
                            </a>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $collection->visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($collection->visibility) }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ trans_choice('{0} No saved products|{1} 1 saved product|[2,*] :count saved products', $collection->items_count, ['count' => $collection->items_count]) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('collections.show', $collection->publicRouteParameters()) }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            View
                        </a>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <form method="POST" action="{{ route('collections.update', $collection) }}" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_auto] md:items-end">
                        @csrf
                        @method('PATCH')

                        @php
                            $nameFieldId = 'collection-name-' . $collection->id;
                            $visibilityFieldId = 'collection-visibility-' . $collection->id;
                        @endphp

                        <div>
                            <x-input-label :for="$nameFieldId" :value="__('Name')" />
                            <x-text-input :id="$nameFieldId" name="name" type="text" class="mt-1 block w-full" :value="$collection->name" maxlength="120" />
                        </div>

                        <div>
                            <x-input-label :for="$visibilityFieldId" :value="__('Visibility')" />
                            <select id="{{ $visibilityFieldId }}" name="visibility" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="public" @selected($collection->visibility === 'public')>Public</option>
                                <option value="private" @selected($collection->visibility === 'private')>Private</option>
                            </select>
                        </div>

                        <div>
                            <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('collections.destroy', $collection) }}" onsubmit="return confirm('Delete this collection and remove all saved products from it?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-600">
                You have not created any collections yet. Use one of the suggested starter names above or create your own.
            </div>
        @endforelse
    </div>
</section>
