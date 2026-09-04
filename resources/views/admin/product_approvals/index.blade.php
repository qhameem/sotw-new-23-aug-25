@extends('layouts.app')

@php
    $hideSidebar = true;
    $mainContentMaxWidth = 'max-w-none';
    $containerMaxWidth = 'max-w-none';
@endphp

@section('header-title', 'Product Approvals')

@section('actions')
@endsection

@section('content')
<style>
    .custom-category-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2147483647 !important;
        display: none !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100vw !important;
        height: 100vh !important;
        height: 100dvh !important;
        overflow: hidden !important;
        padding: 16px !important;
        background: rgb(15 23 42 / 0.65) !important;
    }
    .custom-category-modal.is-open { display: flex !important; }
    .custom-category-modal-panel {
        display: flex !important;
        flex-direction: column !important;
        width: min(672px, 100%) !important;
        height: calc(100vh - 32px) !important;
        height: calc(100dvh - 32px) !important;
        max-height: 760px !important;
        overflow: hidden !important;
    }
    .custom-category-modal-header,
    .custom-category-modal-footer { flex: 0 0 auto !important; }
    .custom-category-modal-body {
        flex: 1 1 auto !important;
        min-height: 0 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }
    .custom-category-modal { font-size: 13px !important; }
    .custom-category-modal h2 { font-size: 16px !important; line-height: 1.35 !important; }
    .custom-category-modal .custom-category-name { font-size: 14px !important; }
    .custom-category-modal textarea { font-size: 13px !important; line-height: 1.4 !important; }
</style>
<div class="mx-auto w-full max-w-none px-4 py-10 sm:px-6 lg:px-8">
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-green-300 bg-green-50 px-4 py-3 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="font-semibold text-gray-900">Success</span>
                <span class="text-sm text-gray-600">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 shadow-sm">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-500">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.66 18h16.68a1 1 0 00.87-1.5l-7.5-13a1 1 0 00-1.74 0z" />
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="font-semibold text-gray-900">Action needed</span>
                <span class="text-sm text-gray-600">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="mb-8 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Product Approvals</h1>
                <p class="mt-1 text-sm text-slate-600">Review pending submissions, manage scheduled launches, and publish approved products faster.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.product-approvals.index', ['status' => 'pending']) }}#pending-approval"
                    @class([
                        'rounded-xl border px-4 py-3 transition hover:border-slate-400 hover:bg-slate-100',
                        'border-slate-500 bg-slate-100 ring-2 ring-slate-200' => $status === 'pending',
                        'border-slate-200 bg-slate-50' => $status !== 'pending',
                    ])>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Pending</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $pendingProducts->count() }}</div>
                </a>
                <a href="{{ route('admin.product-approvals.index', ['status' => 'scheduled', 'sort_by' => 'published_at', 'sort_direction' => 'asc']) }}#approved-products"
                    @class([
                        'rounded-xl border px-4 py-3 transition hover:border-sky-400 hover:bg-sky-100',
                        'border-sky-500 bg-sky-100 ring-2 ring-sky-200' => $status === 'scheduled',
                        'border-sky-200 bg-sky-50' => $status !== 'scheduled',
                    ])>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Scheduled</div>
                    <div class="mt-1 text-2xl font-semibold text-sky-900">{{ $scheduledProductsCount }}</div>
                </a>
                <a href="{{ route('admin.product-approvals.index', ['status' => 'shown', 'sort_by' => 'published_at', 'sort_direction' => 'desc']) }}#approved-products"
                    @class([
                        'rounded-xl border px-4 py-3 transition hover:border-emerald-400 hover:bg-emerald-100',
                        'border-emerald-500 bg-emerald-100 ring-2 ring-emerald-200' => $status === 'shown',
                        'border-emerald-200 bg-emerald-50' => $status !== 'shown',
                    ])>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Shown</div>
                    <div class="mt-1 text-2xl font-semibold text-emerald-900">{{ $shownProductsCount }}</div>
                </a>
            </div>
        </div>
    </div>

    @if (isset($scheduledProductsStats))
        <div class="mb-8 rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:px-8">
            <div class="flex flex-col gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Scheduled Products</h2>
                    <p class="mt-1 text-sm text-slate-500">Upcoming scheduled launch counts, now shown inline instead of in the sidebar.</p>
                </div>

                @if(!$scheduledProductsStats->isEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="py-3 text-left font-semibold text-slate-500">Date</th>
                                    <th class="py-3 text-right font-semibold text-slate-500">Scheduled</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduledProductsStats as $stat)
                                    <tr class="border-b border-slate-100 last:border-b-0">
                                        <td class="py-3 text-slate-700">{{ \Carbon\Carbon::parse($stat->date)->format('d M, Y') }}</td>
                                        <td class="py-3 text-right font-semibold text-slate-900">{{ $stat->count }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-slate-500">No products are currently scheduled.</p>
                @endif
            </div>
        </div>
    @endif

    @if($status === null || $status === 'pending')
    <div id="pending-approval" class="mb-10 scroll-mt-6">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Pending Approval</h2>
                <p class="text-sm text-slate-500">Approve individually or schedule multiple products in one pass.</p>
            </div>
        </div>

        @if($pendingProducts->count() > 0)
            <form action="{{ route('admin.product-approvals.bulk-approve') }}" method="POST" id="bulk-approve-form" class="mb-5 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                @csrf
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Bulk approve pending products</h3>
                        <p class="text-sm text-slate-500">Pick a launch date, then approve every checked card below.</p>
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <label class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" id="select-all" class="mr-2 h-5 w-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                            <span>Select all pending</span>
                        </label>
                        <x-scheduled-datepicker name="bulk_published_at" value="{{ today('UTC')->next(\Carbon\Carbon::MONDAY)->toDateString() }}" />
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700">Approve selected</button>
                    </div>
                </div>
            </form>

            <div class="space-y-3">
                @foreach($pendingProducts as $product)
                    @include('admin.product_approvals._product_approval_card', ['product' => $product])
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-slate-300 bg-white py-10 text-center text-gray-500 shadow-sm">
                <p>No products are currently pending approval.</p>
            </div>
        @endif
    </div>
    @endif

    @php
        $scheduledOnPageCount = $approvedProducts->getCollection()
            ->filter(fn ($product) => !$product->is_published && !is_null($product->published_at))
            ->count();
        $linkParams = array_filter(['per_page' => $perPage, 'status' => $status]);
        $sortArrow = fn($column) => ($sortBy === $column ? ($sortDirection === 'asc' ? '&uarr;' : '&darr;') : '');
    @endphp

    @if($status !== 'pending')
    <div id="approved-products" class="scroll-mt-6">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">
                    {{ $status === 'scheduled' ? 'Scheduled Products' : ($status === 'shown' ? 'Shown Products' : 'Approved Products') }}
                </h2>
                <p class="text-sm text-slate-500">Scheduled products can be published immediately in bulk from here.</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ $scheduledProductsCount }}</span> scheduled total,
                <span class="font-semibold text-slate-900">{{ $scheduledOnPageCount }}</span> on this page
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <form method="GET" action="{{ route('admin.product-approvals.index') }}" class="inline-flex items-center">
                        <label for="per_page" class="mr-2 text-sm font-medium text-slate-600">Show:</label>
                        <select name="per_page" id="per_page" class="rounded-xl border-slate-300 text-sm shadow-sm" onchange="this.form.submit()">
                            <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="sort_direction" value="{{ $sortDirection }}">
                        @if($status)
                            <input type="hidden" name="status" value="{{ $status }}">
                        @endif
                    </form>

                    <form action="{{ route('admin.product-approvals.publish-scheduled-now') }}" method="POST" id="bulk-publish-now-form" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                        @csrf
                        <label class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" id="select-all-scheduled" class="mr-2 h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Select scheduled on this page</span>
                        </label>
                        <button type="submit" name="publish_scope" value="selected" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition enabled:hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" onclick="return confirm('Publish the selected scheduled products immediately?')" {{ $scheduledOnPageCount === 0 ? 'disabled' : '' }}>
                            Publish selected scheduled now
                        </button>
                        <button type="submit" name="publish_scope" value="all" class="inline-flex items-center justify-center rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 transition enabled:hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50" onclick="return confirm('Publish all scheduled products immediately?')" {{ $scheduledProductsCount === 0 ? 'disabled' : '' }}>
                            Publish all scheduled now
                        </button>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-slate-200">
                    <colgroup>
                        <col class="w-[5%]">
                        <col class="w-[25%]">
                        <col class="w-[14%]">
                        <col class="w-[22%]">
                        <col class="w-[13%]">
                        <col class="w-[10%]">
                        <col class="w-[11%]">
                    </colgroup>
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Select</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <a href="{{ route('admin.product-approvals.index', array_merge($linkParams, ['sort_by' => 'name', 'sort_direction' => $sortBy === 'name' && $sortDirection === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-slate-700">
                                    Product {!! $sortArrow('name') !!}
                                </a>
                            </th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">User</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Categories</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <a href="{{ route('admin.product-approvals.index', array_merge($linkParams, ['sort_by' => 'published_at', 'sort_direction' => $sortBy === 'published_at' && $sortDirection === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-slate-700">
                                    Publish Date {!! $sortArrow('published_at') !!}
                                </a>
                            </th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($approvedProducts as $product)
                            @php
                                $isScheduled = !$product->is_published && !is_null($product->published_at);
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/80">
                                <td class="px-3 py-4">
                                    @if($isScheduled)
                                        <input type="checkbox" name="products[]" value="{{ $product->id }}"
                                            class="scheduled-product-checkbox h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            form="bulk-publish-now-form">
                                    @else
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <div class="flex items-start gap-3">
                                        <img src="{{ $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="Logo" class="mt-0.5 h-10 w-10 flex-shrink-0 rounded-xl border bg-gray-100 object-cover">
                                        <div class="min-w-0">
                                            <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow" class="font-semibold text-slate-900 hover:underline">{{ $product->name }}</a>
                                            <p class="mt-1 break-words text-sm text-slate-600">{{ Str::limit($product->tagline, 90) }}</p>
                                            <div class="mt-3">
                                                @include('admin.products._submission_source', ['product' => $product])
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-sm text-slate-700">
                                    <div class="break-words font-medium text-slate-900">{{ $product->user->name ?? 'N/A' }}</div>
                                    <div class="mt-1 break-all text-xs text-slate-500">{{ $product->user->email ?? 'No email' }}</div>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($product->categories as $cat)
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $cat->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-sm text-slate-700">
                                    @if($product->published_at)
                                        <div class="font-medium text-slate-900">{{ $product->published_at->copy()->timezone('UTC')->format('M d, Y') }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $product->published_at->copy()->timezone('UTC')->format('H:i') }} UTC</div>
                                    @else
                                        <span class="text-slate-400">Not set</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    @if($isScheduled)
                                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">Scheduled</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Published</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    <div class="flex flex-col items-start gap-2 text-sm">
                                        <a href="{{ route('admin.products.edit', $product->id) }}?from=approvals" class="font-medium text-indigo-600 hover:underline">Edit</a>
                                        <form action="{{ route('admin.product-approvals.disapprove', $product) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="font-medium text-red-600 hover:underline" onclick="return confirm('Are you sure you want to disapprove this product?')">Disapprove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-400">No approved products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                {{ $approvedProducts->links() }}
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="custom-category-modal-"]').forEach(modal => {
        document.body.appendChild(modal);
    });

    const bindSelectAll = (masterId, checkboxSelector) => {
        const master = document.getElementById(masterId);
        const checkboxes = document.querySelectorAll(checkboxSelector);

        master?.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = master.checked;
                }
            });
        });
    };

    bindSelectAll('select-all', '.product-checkbox');
    bindSelectAll('select-all-scheduled', '.scheduled-product-checkbox');

    const openCategoryModal = productId => {
        const modal = document.getElementById(`custom-category-modal-${productId}`);
        modal?.classList.add('is-open');
        document.body.classList.add('overflow-hidden');
    };

    document.querySelectorAll('[data-custom-category-open]').forEach(button => {
        button.addEventListener('click', () => openCategoryModal(button.dataset.customCategoryOpen));
    });

    document.querySelectorAll('[data-custom-category-close]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('[role="dialog"]');
            modal?.classList.remove('is-open');
            document.body.classList.remove('overflow-hidden');
        });
    });

    document.querySelectorAll('.js-publish-approval-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            if (this.dataset.hasPendingCategories === '1') {
                event.preventDefault();
                openCategoryModal(this.dataset.productId);
                return;
            }

            if (this.querySelector('[name="publish_option"]')?.value === 'specific_date') {
                const productId = this.dataset.productId;
                const dateInput = document.querySelector(`[name="published_at[${productId}]"]`);
                this.querySelector('[name="published_at"]').value = dateInput?.value || '';
            }

            this.querySelectorAll('button').forEach(button => button.disabled = true);
            this.querySelector('.js-publish-spinner')?.classList.remove('hidden');
            const label = this.querySelector('.js-publish-label');
            if (label) label.textContent = 'Publishing...';
        });
    });

    const saveCustomCategory = async form => {
            const error = form.querySelector('.js-custom-category-error');
            error.classList.add('hidden');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                    body: new FormData(form),
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(validationMessage || data.message || 'Unable to save category.');
                }

                const productId = form.dataset.productId;
                form.remove();
                if (data.remaining_count === 0) {
                    document.querySelectorAll(`.js-publish-approval-form[data-product-id="${productId}"]`).forEach(publishForm => {
                        publishForm.dataset.hasPendingCategories = '0';
                    });
                    document.querySelector(`[data-custom-category-open="${productId}"]`)?.remove();
                    const modal = document.getElementById(`custom-category-modal-${productId}`);
                    modal?.classList.remove('is-open');
                    document.body.classList.remove('overflow-hidden');
                }
                return true;
            } catch (exception) {
                error.textContent = exception.message;
                error.classList.remove('hidden');
                error.scrollIntoView({ block: 'center' });
                return false;
            }
    };

    document.querySelectorAll('.js-save-all-custom-categories').forEach(button => {
        button.addEventListener('click', async function() {
            const modal = this.closest('[role="dialog"]');
            const forms = Array.from(modal.querySelectorAll('.js-custom-category-form'));

            if (!forms.every(form => form.reportValidity())) {
                return;
            }

            this.disabled = true;
            this.textContent = 'Saving...';

            for (const form of forms) {
                if (!await saveCustomCategory(form)) {
                    this.disabled = false;
                    this.textContent = 'Save categories';
                    return;
                }
            }
        });
    });

    document.querySelectorAll('.js-custom-category-form').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            form.closest('[role="dialog"]')?.querySelector('.js-save-all-custom-categories')?.click();
        });
    });
});
</script>
@endpush
