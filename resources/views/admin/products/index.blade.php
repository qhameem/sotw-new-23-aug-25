@extends('layouts.app')

@php
    $mainContentMaxWidth = 'max-w-none';
    $containerMaxWidth = 'max-w-[1680px]';
    $hideSidebar = true;
    $headerPadding = 'px-4 sm:px-6 lg:pl-[126px] lg:pr-[122px]';
    $mainPadding = 'px-4 sm:px-6 lg:pl-[126px] lg:pr-[122px]';
    $hasActiveFilters = ($searchTerm ?? '') !== '' || $sortBy !== 'created_at' || $sortDir !== 'desc';
    $showingFrom = $products->firstItem() ?? 0;
    $showingTo = $products->lastItem() ?? 0;
@endphp

@section('title', 'Admin Products')

@section('hide_desktop_page_header')
    1
@endsection

@section('content')
    <div class="w-full py-6 space-y-6">
        <div>
            <h1 class="site-heading-text text-xl md:text-[2.125rem] font-semibold tracking-tight text-slate-950">Products</h1>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <section class="dashboard-panel p-5 lg:p-6">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.85fr)]">
                <div class="space-y-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <p class="dashboard-section-label">Catalog operations</p>
                            <div>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                    Search, sort, reassign, promote, and review products from one streamlined admin workspace.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.product-claims.index') }}" class="dashboard-secondary-button">
                                Claims queue
                            </a>
                            <a href="{{ route('admin.products.create') }}" class="dashboard-primary-button">
                                New product
                            </a>
                        </div>
                    </div>

                    <form action="{{ route('admin.products.index') }}" method="GET" class="space-y-4"
                        x-data="adminProductSearchAutocomplete({
                            initialQuery: @js($searchTerm ?? ''),
                            sortBy: @js($sortBy),
                            sortDir: @js($sortDir),
                        })"
                        @keydown.escape.window="open = false">
                        <div class="space-y-2">
                            <label for="admin-product-search" class="dashboard-label block">
                                Search products
                            </label>

                            <div class="relative" @click.outside="open = false">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start">
                                    <div class="relative flex-1">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 3.5A5.5 5.5 0 1 0 14.5 9 5.506 5.506 0 0 0 9 3.5Zm-7 5.5a7 7 0 1 1 12.026 4.889l3.292 3.293a.75.75 0 1 1-1.06 1.06l-3.293-3.292A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                                            </svg>
                                        </div>

                                        <input id="admin-product-search" type="text" name="q" x-model="query" @input="fetchSuggestions"
                                            @focus="fetchSuggestions"
                                            @keydown.arrow-down.prevent="highlightNext"
                                            @keydown.arrow-up.prevent="highlightPrevious"
                                            @keydown.enter="handleEnter"
                                            placeholder="Search by name, tagline, slug, domain, owner, email, or category"
                                            class="dashboard-input pl-10 pr-4">
                                    </div>

                                    <button type="submit" class="dashboard-primary-button lg:min-w-[124px] lg:self-stretch">
                                        Search
                                    </button>
                                </div>

                                <div x-show="open" x-cloak
                                    class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.08)]">
                                    <template x-if="loading">
                                        <div class="px-4 py-3 text-sm text-slate-500">Searching products...</div>
                                    </template>

                                    <template x-if="!loading && suggestions.length === 0 && query.trim().length >= 2">
                                        <div class="px-4 py-3 text-sm text-slate-500">No matching products found.</div>
                                    </template>

                                    <template x-if="!loading && suggestions.length > 0">
                                        <div>
                                            <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
                                                <a :href="suggestion.select_url"
                                                    @mouseenter="activeIndex = index"
                                                    class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 transition last:border-b-0 hover:bg-slate-50"
                                                    :class="{ 'bg-slate-50': activeIndex === index }">
                                                    <img :src="suggestion.logo_url" alt="" class="h-10 w-10 rounded-lg border border-slate-200 bg-slate-50 object-cover">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="truncate text-sm font-semibold text-slate-900" x-text="suggestion.name"></span>
                                                            <span x-show="suggestion.domain" class="dashboard-badge" x-text="suggestion.domain"></span>
                                                        </div>
                                                        <div class="truncate text-xs text-slate-500" x-text="suggestion.tagline"></div>
                                                        <div class="truncate text-xs text-slate-400">
                                                            <span x-text="suggestion.owner_name || 'No owner'"></span>
                                                            <span x-show="suggestion.owner_email">· <span x-text="suggestion.owner_email"></span></span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </template>

                                            <div class="border-t border-slate-100 px-4 py-3">
                                                <button type="submit" class="text-sm font-medium text-slate-700 hover:text-slate-950">
                                                    Search all results for "<span x-text="query"></span>"
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 lg:grid-cols-[180px_160px_auto]">
                            <div class="space-y-2">
                                <label for="admin-sort-by" class="dashboard-label block">Sort by</label>
                                <select id="admin-sort-by" name="sort_by" x-model="sortBy" class="dashboard-select">
                                    <option value="created_at">Created date</option>
                                    <option value="name">Name</option>
                                    <option value="id">Product ID</option>
                                    <option value="is_promoted">Promotion</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label for="admin-sort-dir" class="dashboard-label block">Order</label>
                                <select id="admin-sort-dir" name="sort_dir" x-model="sortDir" class="dashboard-select">
                                    <option value="desc">Descending</option>
                                    <option value="asc">Ascending</option>
                                </select>
                            </div>

                            <div class="flex flex-wrap items-end gap-2">
                                <button type="submit" class="dashboard-secondary-button">
                                    Apply filters
                                </button>

                                @if ($hasActiveFilters)
                                    <a href="{{ route('admin.products.index') }}" class="dashboard-secondary-button">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                            <span>Live suggestions open inline. Press Enter to open the highlighted result.</span>
                            @if ($searchTerm ?? null)
                                <span class="dashboard-badge">Query: {{ $searchTerm }}</span>
                            @endif
                        </div>
                    </form>
                </div>

                <aside class="dashboard-subpanel p-5">
                    <div class="space-y-4">
                        <div>
                            <p class="dashboard-section-label">Snapshot</p>
                            <h2 class="mt-1 text-base font-semibold text-slate-950">Current result set</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Review the current page, then apply bulk cleanup carefully.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-2">
                            <div class="dashboard-metric">
                                <p class="dashboard-section-label">Results</p>
                                <p class="dashboard-emphasis mt-2 text-2xl font-semibold tracking-tight">{{ number_format($products->total()) }}</p>
                                <p class="dashboard-muted mt-1 text-sm">Matching the current filters</p>
                            </div>

                            <div class="dashboard-metric">
                                <p class="dashboard-section-label">Showing</p>
                                <p class="dashboard-emphasis mt-2 text-2xl font-semibold tracking-tight">{{ $showingFrom }}-{{ $showingTo }}</p>
                                <p class="dashboard-muted mt-1 text-sm">Rows on this page</p>
                            </div>

                            <div class="dashboard-metric">
                                <p class="dashboard-section-label">Selection</p>
                                <p class="dashboard-emphasis mt-2 text-2xl font-semibold tracking-tight"><span id="selected-count-mirror">0</span></p>
                                <p class="dashboard-muted mt-1 text-sm">Checked on the current page</p>
                            </div>

                            <div class="dashboard-metric">
                                <p class="dashboard-section-label">Sort</p>
                                <p class="dashboard-emphasis mt-2 text-base font-semibold">{{ ucfirst(str_replace('_', ' ', $sortBy)) }}</p>
                                <p class="dashboard-muted mt-1 text-sm">{{ $sortDir === 'asc' ? 'Ascending order' : 'Descending order' }}</p>
                            </div>
                        </div>

                        <form id="bulk-delete-form" action="{{ url('/temporary-bulk-delete-test-no-name') }}" method="POST" class="border-t border-slate-200 pt-4">
                            @csrf

                            <div class="space-y-4">
                                <label for="select-all-products" class="flex items-start gap-3 text-sm text-slate-700">
                                    <input type="checkbox" id="select-all-products" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10">
                                    <span>
                                        <span class="block font-semibold text-slate-900">Select all on this page</span>
                                        <span class="mt-1 block text-slate-500">Bulk selection applies only to the visible results below.</span>
                                    </span>
                                </label>

                                <button type="submit" id="bulk-delete-button" class="dashboard-danger-button w-full" disabled>
                                    Delete selected (<span id="selected-count">0</span>)
                                </button>
                            </div>
                        </form>
                    </div>
                </aside>
            </div>
        </section>

        @if ($selectedProduct)
            <section id="selected-product-card" class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4">
                <div class="mb-4 px-1">
                    <p class="dashboard-section-label text-indigo-600">Selected product</p>
                    <p class="mt-1 text-sm text-indigo-950">Loaded inline from the search results. Owner assignment is available below.</p>
                </div>

                <x-admin.product-list-item :product="$selectedProduct" :selected="true" :search-term="$searchTerm" :sort-by="$sortBy" :sort-dir="$sortDir" :bulk-selectable="false" />
            </section>
        @endif

        <section class="space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="dashboard-section-label">Product list</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-950">Catalog results</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Showing {{ $showingFrom }} to {{ $showingTo }} of {{ number_format($products->total()) }} products.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($searchTerm ?? null)
                        <span class="dashboard-badge">Search: {{ $searchTerm }}</span>
                    @endif
                    <span class="dashboard-badge">Sort: {{ ucfirst(str_replace('_', ' ', $sortBy)) }}</span>
                    <span class="dashboard-badge">{{ $sortDir === 'asc' ? 'Ascending' : 'Descending' }}</span>
                    <span class="dashboard-badge">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
                </div>
            </div>

            @if ($products->isEmpty())
                <div class="dashboard-panel border-dashed px-6 py-16 text-center">
                    <p class="text-lg font-medium text-slate-900">No products found.</p>
                    <p class="mt-2 text-sm text-slate-500">Try a broader search term or reset the current filters.</p>
                    @if ($hasActiveFilters)
                        <div class="mt-6">
                            <a href="{{ route('admin.products.index') }}" class="dashboard-secondary-button">
                                Reset filters
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($products as $product)
                        <x-admin.product-list-item :product="$product" :search-term="$searchTerm" :sort-by="$sortBy" :sort-dir="$sortDir" />
                    @endforeach
                </div>
            @endif
        </section>

        @if ($products->hasPages())
            <div class="mt-6">
                {{ $products->onEachSide(1)->links('admin.products.pagination') }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('select-all-products');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkDeleteButton = document.getElementById('bulk-delete-button');
    const selectedCountSpan = document.getElementById('selected-count');
    const selectedCountMirror = document.getElementById('selected-count-mirror');

    function updateSelectedCount() {
        const count = document.querySelectorAll('.product-checkbox:checked').length;

        if (selectedCountSpan) {
            selectedCountSpan.textContent = count;
        }

        if (selectedCountMirror) {
            selectedCountMirror.textContent = count;
        }

        if (bulkDeleteButton) {
            bulkDeleteButton.disabled = count === 0;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedCount();
        });
    }

    if (productCheckboxes.length > 0) {
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (selectAllCheckbox) {
                    if (!this.checked) {
                        selectAllCheckbox.checked = false;
                    } else {
                        let allChecked = true;
                        productCheckboxes.forEach(cb => {
                            if (!cb.checked) {
                                allChecked = false;
                            }
                        });
                        selectAllCheckbox.checked = allChecked;
                    }
                }

                updateSelectedCount();
            });
        });
    }

    updateSelectedCount();
});

function adminProductOwnerManager(config) {
    return {
        open: false,
        query: '',
        results: [],
        selectedUser: null,
        searching: false,
        assigning: false,
        feedback: null,
        searchTimeout: null,
        async searchUsers() {
            clearTimeout(this.searchTimeout);
            this.selectedUser = null;
            this.feedback = null;

            if (this.query.trim().length < 2) {
                this.results = [];
                this.searching = false;
                return;
            }

            this.searchTimeout = setTimeout(async () => {
                this.searching = true;
                try {
                    const response = await fetch(`/admin/users-search-ajax?q=${encodeURIComponent(this.query.trim())}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    this.results = response.ok ? await response.json() : [];
                } catch (error) {
                    this.results = [];
                } finally {
                    this.searching = false;
                }
            }, 250);
        },
        selectUser(user) {
            this.selectedUser = user;
            this.query = `${user.name} (${user.email})`;
            this.results = [];
        },
        async assignOwner() {
            if (!this.selectedUser || this.assigning) {
                return;
            }

            this.assigning = true;
            this.feedback = null;

            try {
                const response = await fetch('/admin/products/assign', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        product_id: config.productId,
                        user_id: this.selectedUser.id
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.feedback = {
                        type: 'error',
                        message: data.message || 'Failed to assign owner.'
                    };
                    return;
                }

                this.feedback = {
                    type: 'success',
                    message: data.message
                };

                window.location.reload();
            } catch (error) {
                this.feedback = {
                    type: 'error',
                    message: 'Failed to assign owner.'
                };
            } finally {
                this.assigning = false;
            }
        }
    };
}

function adminProductSearchAutocomplete(config) {
    return {
        query: config.initialQuery || '',
        sortBy: config.sortBy || 'created_at',
        sortDir: config.sortDir || 'desc',
        suggestions: [],
        open: false,
        loading: false,
        activeIndex: -1,
        searchTimeout: null,
        async fetchSuggestions() {
            clearTimeout(this.searchTimeout);

            if (this.query.trim().length < 2) {
                this.suggestions = [];
                this.open = false;
                this.loading = false;
                this.activeIndex = -1;
                return;
            }

            this.searchTimeout = setTimeout(async () => {
                this.loading = true;
                this.open = true;
                this.activeIndex = -1;

                try {
                    const params = new URLSearchParams({
                        q: this.query.trim(),
                        sort_by: this.sortBy,
                        sort_dir: this.sortDir,
                    });

                    const response = await fetch(`/admin/products/autocomplete?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    this.suggestions = response.ok ? await response.json() : [];
                } catch (error) {
                    this.suggestions = [];
                } finally {
                    this.loading = false;
                }
            }, 220);
        },
        highlightNext() {
            if (!this.open || this.suggestions.length === 0) {
                return;
            }

            this.activeIndex = this.activeIndex < this.suggestions.length - 1 ? this.activeIndex + 1 : 0;
        },
        highlightPrevious() {
            if (!this.open || this.suggestions.length === 0) {
                return;
            }

            this.activeIndex = this.activeIndex > 0 ? this.activeIndex - 1 : this.suggestions.length - 1;
        },
        handleEnter(event) {
            if (this.open && this.activeIndex >= 0 && this.suggestions[this.activeIndex]) {
                event.preventDefault();
                window.location.href = this.suggestions[this.activeIndex].select_url;
            }
        }
    };
}
</script>
@endpush
