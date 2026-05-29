@props([
    'product',
    'selected' => false,
    'searchTerm' => null,
    'sortBy' => 'created_at',
    'sortDir' => 'desc',
    'bulkSelectable' => true,
])

@php
    use App\Support\ProductLogo;

    $productDomain = parse_url($product->link, PHP_URL_HOST);
    $productDomain = is_string($productDomain) ? preg_replace('/^www\./i', '', $productDomain) : null;
    $visibleCategories = $product->categories->take(3);
    $showVoteBreakdown = $product->approved && $product->is_published;
    $voteBreakdown = $showVoteBreakdown ? $product->voteBreakdown() : null;
    $storedLogoUrl = ProductLogo::storedUrl($product);
    $isMissingLogo = $storedLogoUrl === null;
@endphp

<article x-data="adminProductOwnerManager({
        productId: {{ $product->id }},
        logoUpdateUrl: @js(route('admin.products.panel-logo', $product)),
        logoUrl: @js($storedLogoUrl),
        logoInitial: @js(ProductLogo::initial($product)),
        productName: @js($product->name),
    })"
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

                <img
                    x-show="logoUrl"
                    src="{{ $storedLogoUrl ?: '' }}"
                    :src="logoUrl"
                    alt="{{ $product->name }} logo"
                    class="h-12 w-12 flex-shrink-0 rounded-xl border border-slate-200 bg-slate-50 object-cover"
                    loading="lazy"
                    @if (!$storedLogoUrl) style="display: none;" @endif
                />
                <div
                    x-show="!logoUrl"
                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-base font-semibold text-slate-400"
                    @if ($storedLogoUrl) style="display: none;" @endif
                >
                    <span x-text="logoInitial"></span>
                </div>

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
                                <span x-show="!logoUrl" class="dashboard-badge-warning" @if (!$isMissingLogo) style="display: none;" @endif>
                                    Missing logo
                                </span>
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

                            @if ($product->is_published)
                                <span class="dashboard-badge-success">Published</span>
                            @else
                                <span class="dashboard-badge-warning">Unpublished</span>
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
            @if ($showVoteBreakdown && $voteBreakdown)
                <section class="dashboard-subpanel p-3 xl:col-span-2">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="dashboard-section-label">Upvote sources</p>
                            <h3 class="mt-1 text-sm font-semibold text-slate-950 sm:text-base">
                                Where {{ number_format($voteBreakdown['total']) }} votes came from
                            </h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500 sm:text-sm">
                                Views and outbound clicks are tracked separately now. Any non-manual remainder is legacy vote history.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-1.5 lg:max-w-[360px] lg:justify-end">
                            <span class="dashboard-badge px-2 py-0.5 text-[11px]">{{ number_format((int) ($product->impressions ?? 0)) }} views</span>
                            <span class="dashboard-badge px-2 py-0.5 text-[11px]">{{ number_format((int) ($product->outbound_clicks_count ?? 0)) }} link clicks</span>
                            <span class="dashboard-badge px-2 py-0.5 text-[11px]">{{ number_format($voteBreakdown['total']) }} total votes</span>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($voteBreakdown['sources'] as $source)
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5">
                                <p class="dashboard-section-label">{{ $source['label'] }}</p>
                                <div class="mt-1 flex items-end justify-between gap-3">
                                    <p class="dashboard-emphasis text-xl font-semibold tracking-tight sm:text-2xl">
                                        {{ number_format($source['count']) }}
                                    </p>
                                    <p class="dashboard-muted text-xs text-right">{{ $source['percentage_label'] }} of votes</p>
                                </div>
                                <p class="mt-1 text-[11px] leading-4 text-slate-400 sm:text-xs">{{ $source['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="dashboard-subpanel p-4">
                <div class="space-y-4">
                    <div>
                        <p class="dashboard-section-label">Logo tools</p>
                        <h3 class="mt-1 text-base font-semibold text-slate-950">Update the live logo</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Admin logo changes save immediately from this panel and do not go through review.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-3">
                        <img
                            x-show="logoUrl"
                            src="{{ $storedLogoUrl ?: '' }}"
                            :src="logoUrl"
                            alt="{{ $product->name }} logo preview"
                            class="h-12 w-12 rounded-xl border border-slate-200 bg-slate-50 object-cover"
                            @if (!$storedLogoUrl) style="display: none;" @endif
                        />
                        <div
                            x-show="!logoUrl"
                            class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-base font-semibold text-slate-400"
                            @if ($storedLogoUrl) style="display: none;" @endif
                        >
                            <span x-text="logoInitial"></span>
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">Current logo</p>
                            <p class="mt-1 text-xs text-slate-500" x-text="logoUrl ? 'Live logo is set.' : 'No live logo is set yet.'"></p>
                        </div>
                    </div>

                    <input
                        x-ref="logoInput"
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp,image/avif,.jpg,.jpeg,.png,.gif,.svg,.webp,.avif"
                        class="hidden"
                        @change="handleLogoFileChange"
                    >

                    <div x-show="logoFeedback" x-cloak class="rounded-lg px-3 py-2 text-sm"
                        :class="logoFeedback?.type === 'success'
                            ? 'border border-green-200 bg-green-50 text-green-800'
                            : 'border border-red-200 bg-red-50 text-red-800'">
                        <span x-text="logoFeedback?.message"></span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                            @click="openLogoFilePicker"
                            :disabled="logoSaving"
                            class="dashboard-primary-button disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="!logoSaving">Upload logo</span>
                            <span x-show="logoSaving">Saving...</span>
                        </button>

                        <button type="button"
                            @click="findLogoFromUrl"
                            :disabled="logoSaving"
                            class="dashboard-secondary-button disabled:cursor-not-allowed disabled:opacity-50">
                            Find from URL
                        </button>
                    </div>
                </div>
            </section>

            <div
                x-show="logoModalOpen"
                x-cloak
                class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/50 px-4 py-6"
                @click.self="closeLogoModal"
                @keydown.escape.window="closeLogoModal"
            >
                <div class="w-full max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                        <div>
                            <p class="dashboard-section-label">Extracted logos</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950" x-text="`Choose a logo for ${productName}`"></h3>
                            <p class="mt-1 text-sm text-slate-500">Click any option below to save it immediately as the live logo.</p>
                        </div>

                        <button type="button"
                            @click="closeLogoModal"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                            <span class="sr-only">Close</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto px-5 py-5">
                        <div
                            x-show="logoSaving && extractedLogos.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center"
                        >
                            <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-sky-500"></div>
                            <p class="mt-3 text-sm text-slate-500">Looking for logos from the product URL...</p>
                        </div>

                        <div
                            x-show="!logoSaving && extractedLogos.length === 0"
                            class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center"
                        >
                            <p class="text-sm font-medium text-slate-700">No extracted logos found</p>
                            <p class="mt-1 text-sm text-slate-500">Try uploading a logo instead.</p>
                        </div>

                        <div x-show="extractedLogos.length > 0" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <template x-for="logoOption in extractedLogos" :key="logoOption">
                                <button type="button"
                                    @click="selectExtractedLogo(logoOption)"
                                    :disabled="logoSaving"
                                    class="rounded-2xl border border-slate-200 bg-white p-3 text-left transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
                                    <div class="flex h-24 items-center justify-center rounded-2xl bg-slate-50">
                                        <img :src="logoOption" alt="" class="h-16 w-16 object-contain">
                                    </div>
                                    <div class="mt-3 flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium text-slate-900">Use this logo</span>
                                        <span class="text-xs text-slate-400">Save</span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

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
                            Update promotion settings, edit the product, unpublish it, or remove it from the catalog.
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

                        @if ($product->is_published)
                            <form action="{{ route('admin.products.unpublish', $product) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Are you sure you want to unpublish this product?');">
                                @csrf
                                <button type="submit" class="font-medium text-amber-700 hover:text-amber-900">
                                    Unpublish product
                                </button>
                            </form>
                        @endif

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
