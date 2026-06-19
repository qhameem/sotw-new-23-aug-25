@php
    use App\Support\ProductLogo;

    $productLogo = ProductLogo::storedUrl($product);
    $showingDefaultSuggestions = collect($productCollectionOptions ?? [])->isNotEmpty()
        && collect($productCollectionOptions)->every(fn ($collection) => $collection['is_default'] ?? false);
@endphp

<x-modal name="product-save-modal" :show="false" maxWidth="lg" focusable>
    <div
        x-data="productCollectionSaver({
            syncUrl: {{ Js::from(route('products.collections.sync', $product)) }},
            csrfToken: {{ Js::from(csrf_token()) }},
            collections: {{ Js::from($productCollectionOptions ?? []) }}
        })"
        class="p-6 sm:p-7"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-gray-50">
                    @if($productLogo)
                        <img src="{{ $productLogo }}" alt="{{ $product->name }} logo" class="h-12 w-12 rounded-xl object-contain">
                    @else
                        <span class="text-lg font-semibold text-gray-500">{{ ProductLogo::initial($product) }}</span>
                    @endif
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Save Product</p>
                    <h2 class="mt-1 text-xl font-semibold text-gray-900">{{ $product->name }}</h2>
                    <p class="mt-1 text-sm text-gray-600">Choose one or more collections for this product.</p>
                </div>
            </div>

            <button type="button" @click="$dispatch('close-modal', 'product-save-modal')" class="rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Close">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>

        @if($showingDefaultSuggestions)
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Starter lists are shown because you have not created any collections yet.
            </div>
        @endif

        <div class="mt-6 space-y-3">
            <template x-for="(collection, index) in collections" :key="collection.id ?? collection.default_name ?? index">
                <div class="rounded-xl border border-gray-200 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" x-model="collection.selected" class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900" x-text="collection.name"></span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="collection.visibility === 'public' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700'"
                                    x-text="collection.visibility === 'public' ? 'Public' : 'Private'"></span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500" x-show="collection.is_default">This starter list will be created for you the first time you use it.</p>
                        </div>
                    </label>

                    <div class="mt-3" x-show="collection.selected">
                        <label class="text-xs font-medium uppercase tracking-[0.14em] text-gray-500">Optional note</label>
                        <textarea x-model="collection.comment" rows="2" class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Why are you saving this?"></textarea>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-6 rounded-xl border border-dashed border-gray-300 p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Create a new collection</h3>
                    <p class="mt-1 text-sm text-gray-600">You can save this product into a brand-new list right away.</p>
                </div>
                <button type="button" @click="newCollection.enabled = !newCollection.enabled" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    <span x-text="newCollection.enabled ? 'Hide' : 'Add New'"></span>
                </button>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2" x-show="newCollection.enabled">
                <div>
                    <label class="text-sm font-medium text-gray-700">Collection name</label>
                    <input type="text" x-model="newCollection.name" maxlength="120" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Research tools">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Visibility</label>
                    <select x-model="newCollection.visibility" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Optional note</label>
                    <textarea x-model="newCollection.comment" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Anything you want to remember about this product"></textarea>
                </div>
            </div>
        </div>

        <p x-show="message" x-text="message" class="mt-4 text-sm text-emerald-700"></p>
        <p x-show="errorMessage" x-text="errorMessage" class="mt-4 text-sm text-red-600"></p>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" @click="$dispatch('close-modal', 'product-save-modal')" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </button>

            <button type="button" @click="save()" :disabled="submitting" class="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60">
                <span x-show="!submitting">Save Selection</span>
                <span x-show="submitting" style="display: none;">Saving...</span>
            </button>
        </div>
    </div>
</x-modal>
