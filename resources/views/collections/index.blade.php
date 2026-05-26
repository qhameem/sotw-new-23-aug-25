@php
    $mainContentMaxWidth = 'max-w-none';
    $containerMaxWidth = 'max-w-none';
    $hideSidebar = true;
    $mainPadding = 'px-0';
    $headerPadding = 'px-0';
@endphp

@extends('layouts.app')

@section('title', 'My Collections')

@section('content')
    <div class="mx-auto w-full max-w-[1500px] px-4 py-8 sm:px-6 lg:px-10 xl:px-12">
        <section class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-[0_24px_80px_-48px_rgba(15,23,42,0.35)]">
            <div class="border-b border-gray-100 bg-white px-6 py-8 sm:px-8 lg:px-10 xl:px-12">
                <div class="flex flex-col gap-8 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-gray-400">Collections Workspace</p>
                        <h1 class="mt-4 max-w-2xl text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl xl:text-[3.25rem] xl:leading-[1.02]">
                            Manage your saved products in one place.
                        </h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-gray-600 sm:text-base">
                            Organize products into focused lists for research, favorites, comparisons, and items you want to revisit later. Public lists can be shared, private lists stay just for you.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="#create-collection"
                            class="inline-flex items-center justify-center rounded-full bg-gray-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800"
                        >
                            Create collection
                        </a>
                        <a
                            href="{{ route('profile.edit') }}"
                            class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50"
                        >
                            Back to profile
                        </a>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[1.75rem] border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Collections</p>
                        <p class="mt-4 text-4xl font-semibold tracking-tight text-gray-900">{{ $collectionsCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Total lists in your workspace.</p>
                    </div>

                    <div class="rounded-[1.75rem] border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Saved Products</p>
                        <p class="mt-4 text-4xl font-semibold tracking-tight text-gray-900">{{ $savedProductsCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Products saved across all lists.</p>
                    </div>

                    <div class="rounded-[1.75rem] border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Public Lists</p>
                        <p class="mt-4 text-4xl font-semibold tracking-tight text-gray-900">{{ $publicCollectionsCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Visible to others and ready to share.</p>
                    </div>

                    <div class="rounded-[1.75rem] border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Private Lists</p>
                        <p class="mt-4 text-4xl font-semibold tracking-tight text-gray-900">{{ $privateCollectionsCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Hidden from everyone except you.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-0 border-t border-gray-100 xl:grid-cols-[380px_minmax(0,1fr)]">
                <aside class="border-b border-gray-100 bg-gray-50/70 p-6 xl:border-b-0 xl:border-r xl:p-8">
                    <div class="xl:sticky xl:top-6 space-y-6">
                        <div id="create-collection" class="rounded-[1.75rem] border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">New Collection</p>
                                    <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">Create a fresh list</h2>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-900 text-sm font-semibold text-white">
                                    +
                                </div>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-gray-600">
                                Start with a clear purpose, then choose whether the list should be public or private.
                            </p>

                            @if(session('status') === 'collection-created')
                                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                                    Collection created.
                                </div>
                            @elseif(session('status') === 'collection-updated')
                                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                                    Collection updated.
                                </div>
                            @elseif(session('status') === 'collection-deleted')
                                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                                    Collection deleted.
                                </div>
                            @endif

                            <form method="POST" action="{{ route('collections.store') }}" class="mt-6 space-y-5">
                                @csrf

                                <div>
                                    <x-input-label for="collection_name" :value="__('Collection name')" />
                                    <x-text-input
                                        id="collection_name"
                                        name="name"
                                        type="text"
                                        class="mt-2 block w-full rounded-2xl border-gray-200"
                                        maxlength="120"
                                        placeholder="AI research stack"
                                    />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>

                                <div>
                                    <x-input-label for="collection_visibility" :value="__('Visibility')" />
                                    <select id="collection_visibility" name="visibility" class="mt-2 block w-full rounded-2xl border-gray-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="public">Public</option>
                                        <option value="private">Private</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('visibility')" />
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-full bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700"
                                >
                                    Create Collection
                                </button>
                            </form>
                        </div>

                        <div class="rounded-[1.75rem] border border-dashed border-gray-300 bg-white p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Starter Names</p>
                            <p class="mt-3 text-sm leading-6 text-gray-600">
                                Helpful defaults for anyone who is building their first few lists.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($defaultProductCollectionNames as $defaultProductCollectionName)
                                    <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700">
                                        {{ $defaultProductCollectionName }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 p-6 text-gray-900">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Visibility Guide</p>
                            <div class="mt-4 space-y-3 text-sm leading-6 text-gray-700">
                                <p>Public lists work best for curated recommendations, roundups, and collections you want to share.</p>
                                <p>Private lists are better for comparison shopping, internal research, or products you are not ready to publish yet.</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="bg-white p-6 xl:p-8">
                    <div class="flex flex-col gap-4 border-b border-gray-100 pb-6 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Your Library</p>
                            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900">Collections you can edit, open, and share</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600">
                                Keep names clear, make public collections easy to browse, and use private lists for quieter planning.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs font-medium text-gray-500">
                            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5">
                                {{ $collectionsCount }} total
                            </span>
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-emerald-700">
                                {{ $publicCollectionsCount }} public
                            </span>
                            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-100 px-3 py-1.5 text-gray-700">
                                {{ $privateCollectionsCount }} private
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        @forelse($collections as $collection)
                            @php
                                $collectionNameFieldId = 'collection-name-' . $collection->id;
                                $collectionVisibilityFieldId = 'collection-visibility-' . $collection->id;
                            @endphp

                            @if($loop->first)
                                <div class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
                            @endif

                            <article class="group flex h-full flex-col rounded-[1.75rem] border border-gray-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-lg">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $collection->visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                                <span class="text-lg font-semibold">
                                                    {{ strtoupper(\Illuminate\Support\Str::substr($collection->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('collections.show', $collection->publicRouteParameters()) }}" class="block truncate text-lg font-semibold text-gray-900 transition hover:text-primary-600">
                                                    {{ $collection->name }}
                                                </a>
                                                <p class="mt-1 text-sm text-gray-500">
                                                    Updated {{ $collection->updated_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $collection->visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($collection->visibility) }}
                                    </span>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Saved</p>
                                        <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">{{ $collection->items_count }}</p>
                                        <p class="mt-1 text-xs leading-5 text-gray-500">
                                            {{ trans_choice('{0} No products yet|{1} Product in this list|[2,*] Products in this list', $collection->items_count, ['count' => $collection->items_count]) }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Share</p>
                                        <p class="mt-2 text-sm font-semibold text-gray-900">
                                            {{ $collection->visibility === 'public' ? 'Visible to everyone' : 'Visible only to you' }}
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-gray-500">
                                            {{ $collection->visibility === 'public' ? 'Anyone with the link can open it.' : 'Switch to public any time.' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-wrap gap-3">
                                    <a
                                        href="{{ route('collections.show', $collection->publicRouteParameters()) }}"
                                        class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50"
                                    >
                                        Open collection
                                    </a>

                                    <form method="POST" action="{{ route('collections.destroy', $collection) }}" onsubmit="return confirm('Delete this collection and remove all saved products from it?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-full border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>

                                <form method="POST" action="{{ route('collections.update', $collection) }}" class="mt-5 space-y-4 rounded-[1.5rem] border border-gray-200 bg-white p-4">
                                    @csrf
                                    @method('PATCH')

                                    <div>
                                        <x-input-label :for="$collectionNameFieldId" :value="__('Collection name')" />
                                        <x-text-input
                                            :id="$collectionNameFieldId"
                                            name="name"
                                            type="text"
                                            class="mt-2 block w-full rounded-2xl border-gray-200"
                                            :value="$collection->name"
                                            maxlength="120"
                                        />
                                    </div>

                                    <div>
                                        <x-input-label :for="$collectionVisibilityFieldId" :value="__('Visibility')" />
                                        <select id="{{ $collectionVisibilityFieldId }}" name="visibility" class="mt-2 block w-full rounded-2xl border-gray-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="public" @selected($collection->visibility === 'public')>Public</option>
                                            <option value="private" @selected($collection->visibility === 'private')>Private</option>
                                        </select>
                                    </div>

                                    <button
                                        type="submit"
                                        class="inline-flex w-full items-center justify-center rounded-full border border-gray-300 bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800"
                                    >
                                        Save Changes
                                    </button>
                                </form>
                            </article>

                            @if($loop->last)
                                </div>
                            @endif
                        @empty
                            <div class="rounded-[1.75rem] border border-dashed border-gray-300 bg-[linear-gradient(180deg,_#ffffff_0%,_#f8fafc_100%)] px-6 py-14 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-100 text-2xl text-gray-500">
                                    +
                                </div>
                                <p class="mt-5 text-2xl font-semibold tracking-tight text-gray-900">No collections yet</p>
                                <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-gray-600">
                                    Create your first list from the left panel and start grouping products by favorites, workflows, or ideas you want to revisit.
                                </p>
                                <a
                                    href="#create-collection"
                                    class="mt-6 inline-flex items-center justify-center rounded-full bg-gray-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-gray-800"
                                >
                                    Create your first collection
                                </a>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </div>
@endsection
