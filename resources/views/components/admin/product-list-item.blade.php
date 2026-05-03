@props([
    'product',
    'selected' => false,
    'searchTerm' => null,
    'sortBy' => 'created_at',
    'sortDir' => 'desc',
    'bulkSelectable' => true,
])

@php
    $productDomain = parse_url($product->link, PHP_URL_HOST);
    $productDomain = is_string($productDomain) ? preg_replace('/^www\./i', '', $productDomain) : null;
    $visibleCategories = $product->categories->take(3);
@endphp

<article x-data="adminProductOwnerManager({ productId: {{ $product->id }} })"
    @class([
        'dashboard-panel h-full p-5',
        'border-indigo-200 ring-1 ring-indigo-100 bg-indigo-50/30' => $selected,
    ])>
    <div class="flex h-full flex-col gap-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="flex min-w-0 flex-1 gap-4">
                @if ($bulkSelectable)
                    <div class="pt-1">
                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" form="bulk-delete-form"
                            class="product-checkbox h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10">
                    </div>
                @endif

                <img src="{{ $product->logo_url ?? 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}"
                    alt="{{ $product->name }} logo"
                    class="h-12 w-12 flex-shrink-0 rounded-xl border border-slate-200 bg-slate-50 object-cover"
                    loading="lazy" />

                <div class="min-w-0 flex-1 space-y-4">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.products.index', ['q' => $searchTerm, 'sort_by' => $sortBy, 'sort_dir' => $sortDir, 'selected_product_id' => $product->id]) }}#selected-product-card"
                                    class="truncate text-lg font-semibold tracking-tight text-slate-950 hover:text-slate-700">
                                    {{ $product->name }}
                                </a>
                                <span class="dashboard-badge">#{{ $product->id }}</span>
                                @if ($product->is_promoted)
                                    <span class="dashboard-badge">Promoted</span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $product->tagline }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                            @if ($productDomain)
                                <span class="dashboard-badge">{{ $productDomain }}</span>
                            @endif

                            @if ($product->approved)
                                <span class="dashboard-badge-success">Approved</span>
                            @else
                                <span class="dashboard-badge-warning">Pending</span>
                            @endif
                        </div>
                    </div>

                    <dl class="grid gap-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <dt class="dashboard-section-label">Owner</dt>
                            <dd class="dashboard-emphasis mt-1 font-medium">{{ $product->user->name ?? 'N/A' }}</dd>
                        </div>

                        <div>
                            <dt class="dashboard-section-label">Email</dt>
                            <dd class="dashboard-muted mt-1">{{ $product->user->email ?? 'No email' }}</dd>
                        </div>

                        <div>
                            <dt class="dashboard-section-label">Created</dt>
                            <dd class="dashboard-muted mt-1">{{ $product->created_at?->format('M j, Y') ?? 'Unknown' }}</dd>
                        </div>

                        <div>
                            <dt class="dashboard-section-label">Slug</dt>
                            <dd class="dashboard-muted mt-1 truncate">{{ $product->slug ?: 'Not set' }}</dd>
                        </div>
                    </dl>

                    <div class="flex flex-wrap gap-2">
                        @forelse ($visibleCategories as $category)
                            <span class="dashboard-badge">{{ $category->name }}</span>
                        @empty
                            <span class="text-sm text-slate-500">No categories assigned yet.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <section class="dashboard-subpanel p-4">
                <div class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="dashboard-section-label">Product owner</p>
                            <p class="dashboard-emphasis mt-1 text-base font-semibold">{{ $product->user->name ?? 'N/A' }}</p>
                            @if ($product->user?->email)
                                <p class="mt-1 text-sm text-slate-500">{{ $product->user->email }}</p>
                            @endif
                        </div>

                        <span class="dashboard-badge">{{ $productDomain ?: 'No domain detected' }}</span>
                    </div>

                    <div class="space-y-2">
                        <label for="owner-search-{{ $product->id }}" class="dashboard-label block">
                            Reassign owner
                        </label>

                        <div class="relative" @click.outside="open = false">
                            <input id="owner-search-{{ $product->id }}" type="text" x-model="query" @input="searchUsers"
                                @focus="searchUsers; open = true"
                                placeholder="Search by user name or email"
                                class="dashboard-input">

                            <div x-show="open && (searching || results.length > 0 || query.trim().length >= 2)" x-cloak
                                class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_16px_32px_rgba(15,23,42,0.08)]">
                                <template x-if="searching">
                                    <div class="px-3 py-2 text-sm text-slate-500">Searching users...</div>
                                </template>

                                <template x-if="!searching && results.length === 0 && query.trim().length >= 2">
                                    <div class="px-3 py-2 text-sm text-slate-500">No matching users found.</div>
                                </template>

                                <template x-if="!searching && results.length > 0">
                                    <div class="max-h-56 overflow-y-auto">
                                        <template x-for="user in results" :key="user.id">
                                            <button type="button" @click="selectUser(user); open = false"
                                                class="flex w-full items-center gap-3 border-b border-slate-100 px-3 py-2 text-left transition last:border-b-0 hover:bg-slate-50">
                                                <img :src="user.avatar" alt="" class="h-8 w-8 rounded-full border border-slate-200 bg-slate-50 object-cover">
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-medium text-slate-900" x-text="user.name"></span>
                                                    <span class="block truncate text-xs text-slate-500" x-text="user.email"></span>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div x-show="selectedUser" x-cloak class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-900">
                        Assign to <span class="font-semibold" x-text="selectedUser?.name"></span>
                        <span class="text-indigo-700" x-text="selectedUser?.email ? `(${selectedUser.email})` : ''"></span>
                    </div>

                    <div x-show="feedback" x-cloak class="rounded-lg px-3 py-2 text-sm"
                        :class="feedback?.type === 'success'
                            ? 'border border-green-200 bg-green-50 text-green-800'
                            : 'border border-red-200 bg-red-50 text-red-800'">
                        <span x-text="feedback?.message"></span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="assignOwner" :disabled="!selectedUser || assigning"
                            class="dashboard-primary-button disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="!assigning">Assign owner</span>
                            <span x-show="assigning">Assigning...</span>
                        </button>

                        <button type="button"
                            @click="open = false; query = ''; results = []; selectedUser = null; feedback = null"
                            class="dashboard-secondary-button">
                            Clear
                        </button>
                    </div>
                </div>
            </section>

            <section class="dashboard-subpanel p-4">
                <div class="space-y-4">
                    <div>
                        <p class="dashboard-section-label">Placement controls</p>
                        <h3 class="mt-1 text-base font-semibold text-slate-950">Promotion and actions</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Update promotion settings, edit the product, or remove it from the catalog.
                        </p>
                    </div>

                    <form action="{{ route('admin.products.updatePromotion', $product) }}" method="POST" class="space-y-4">
                        @csrf

                        <label for="is_promoted_{{ $product->id }}" class="flex items-start gap-3 text-sm text-slate-700">
                            <input type="checkbox" id="is_promoted_{{ $product->id }}" name="is_promoted" value="1"
                                @checked($product->is_promoted)
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10">
                            <span>
                                <span class="block font-semibold text-slate-900">Promote this product</span>
                                <span class="mt-1 block text-slate-500">Promoted products stay pinned above regular catalog results.</span>
                            </span>
                        </label>

                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <div class="space-y-2">
                                <label for="promoted_position_{{ $product->id }}" class="dashboard-label block">
                                    Promotion position
                                </label>
                                <input id="promoted_position_{{ $product->id }}" type="number" name="promoted_position"
                                    value="{{ $product->promoted_position }}"
                                    class="dashboard-input"
                                    placeholder="Promotion position">
                            </div>

                            <button type="submit" class="dashboard-primary-button sm:self-end">
                                Save placement
                            </button>
                        </div>
                    </form>

                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4 text-sm">
                        <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-slate-900 hover:text-slate-700">
                            Edit product
                        </a>

                        <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow"
                            class="font-medium text-slate-600 hover:text-slate-900">
                            Open website
                        </a>

                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline-block"
                            onsubmit="return confirm('Are you sure you want to delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dashboard-danger-text font-medium hover:text-red-900">
                                Delete product
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
</article>
