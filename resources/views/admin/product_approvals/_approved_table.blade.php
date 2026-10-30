@php
    $groups = $approvedProducts->getCollection()->groupBy(fn ($product) => !$product->is_published && $product->published_at
        ? $product->published_at->copy()->timezone('UTC')->toDateString() : 'published');
    $filterParams = ['search' => $search, 'submitter_type' => $submitterType, 'per_page' => $perPage, 'sort_by' => 'published_at', 'sort_direction' => $sortDirection];
@endphp
<section id="approved-products" data-approval-table class="approval-panel scroll-mt-6 rounded-xl border border-slate-200 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
        <nav aria-label="Product status" class="flex gap-1">
            @foreach(['' => 'All', 'scheduled' => 'Scheduled', 'shown' => 'Published', 'failed_badge' => 'Failed badge'] as $value => $label)
                <a href="{{ route('admin.product-approvals.index', array_merge($filterParams, ['status' => $value])) }}#approved-products"
                    @if(($status ?? '') === $value) aria-current="page" @endif
                    @class(['rounded-lg px-3 py-2 font-medium transition focus-visible:ring-2 focus-visible:ring-indigo-500', 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' => ($status ?? '') === $value, 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => ($status ?? '') !== $value])>{{ $label }}</a>
            @endforeach
        </nav>
        <label class="flex items-center gap-2 text-slate-600 dark:text-slate-300">Density
            <select data-density aria-label="Table density" class="rounded-lg border-slate-300 bg-transparent py-1 text-sm dark:border-slate-600 dark:bg-slate-900">
                <option value="compact">Compact</option><option value="comfortable">Comfortable</option>
            </select>
        </label>
    </div>
    <form method="GET" action="{{ route('admin.product-approvals.index') }}" class="approval-filters">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="hidden" name="sort_by" value="published_at">
        <div class="approval-search">
            <label class="min-w-0 flex-1"><span class="sr-only">Search products or submitters</span><input type="search" name="search" value="{{ $search }}" placeholder="Search products or submitters…" class="w-full rounded-lg border-slate-300 bg-transparent py-2 text-sm placeholder:text-slate-500 dark:border-slate-600"></label>
            <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 font-medium text-white dark:bg-slate-100 dark:text-slate-900">Search</button>
        </div>
        <label class="flex items-center gap-2 whitespace-nowrap">Submitted by
            <select name="submitter_type" class="rounded-lg border-slate-300 py-2 text-sm dark:border-slate-600 dark:bg-slate-900" onchange="this.form.requestSubmit()">
                <option value="" @selected($submitterType === '')>Everyone</option>
                <option value="admin" @selected($submitterType === 'admin')>Admins</option>
                <option value="non_admin" @selected($submitterType === 'non_admin')>Non-admins</option>
            </select>
        </label>
        <label class="flex items-center gap-2 whitespace-nowrap">Publish date
            <select name="sort_direction" class="rounded-lg border-slate-300 py-2 text-sm dark:border-slate-600 dark:bg-slate-900" onchange="this.form.requestSubmit()">
                <option value="asc" @selected($sortDirection === 'asc')>Earliest first</option><option value="desc" @selected($sortDirection === 'desc')>Latest first</option>
            </select>
        </label>
        <label class="flex items-center gap-2 whitespace-nowrap">Rows
            <select name="per_page" class="rounded-lg border-slate-300 py-2 text-sm dark:border-slate-600 dark:bg-slate-900" onchange="this.form.requestSubmit()">
                @foreach([20, 50, 100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach
            </select>
        </label>
    </form>
    <div class="max-h-[70vh] overflow-auto">
        <table class="w-full min-w-[1280px] table-fixed text-left text-sm">
            <colgroup>
                <col style="width: calc(43% - 240px)">
                <col style="width: 14%">
                <col style="width: 14%">
                <col style="width: 13%">
                <col style="width: 9%">
                <col style="width: 7%">
                <col style="width: 240px">
            </colgroup>
            <thead class="sticky top-0 z-10 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <tr>
                    <th scope="col" class="px-4 py-3"><div class="flex items-center gap-3"><input type="checkbox" data-select-page aria-label="Select all products on this page" class="h-4 w-4 rounded border-slate-400 text-indigo-600">Product</div></th>
                    @foreach(['Submitter', 'Categories', 'Publish date', 'Status', 'Badge', 'Actions'] as $heading)<th scope="col" class="px-2 py-3 text-xs font-semibold">{{ $heading }}</th>@endforeach
                </tr>
            </thead>
            @forelse($groups as $date => $products)
                <tbody>
                    <tr class="bg-slate-50 dark:bg-slate-800/60"><th scope="rowgroup" colspan="7" class="border-y border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $date === 'published' ? 'Published' : \Carbon\Carbon::parse($date)->format('M j, Y').' ('.$products->count().' scheduled)' }}</th></tr>
                    @foreach($products as $product)
                        @include('admin.product_approvals._table_row')
                    @endforeach
                </tbody>
            @empty
                <tbody><tr><td colspan="7" class="px-4 py-8 text-center text-slate-600 dark:text-slate-300">No products match these filters.</td></tr></tbody>
            @endforelse
        </table>
    </div>
    <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-700">{{ $approvedProducts->links() }}</div>
    <form method="POST" action="{{ route('admin.product-approvals.bulk-manage') }}" data-bulk-form
        class="approval-bulk fixed bottom-6 left-1/2 -translate-x-1/2 z-40 flex items-center gap-2 sm:gap-3 rounded-2xl border border-slate-200/90 bg-white/95 px-3 py-2 sm:px-4 sm:py-2.5 shadow-2xl shadow-slate-900/15 backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95 dark:shadow-black/50 transition-all duration-300 ease-out translate-y-24 opacity-0 pointer-events-none"
        aria-hidden="true" inert>
        @csrf
        <div class="flex items-center gap-2">
            <span class="inline-flex h-2 w-2 rounded-full bg-sky-500 animate-pulse"></span>
            <span data-selection-count class="text-xs font-semibold text-slate-800 dark:text-slate-100 whitespace-nowrap" aria-live="polite">0 selected</span>
        </div>

        <span class="h-4 w-px bg-slate-200 dark:bg-slate-700"></span>

        <div data-selected-inputs></div>

        <button type="submit" formaction="{{ route('admin.product-approvals.publish-scheduled-now') }}" name="publish_scope" value="selected"
            class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 disabled:opacity-40 disabled:cursor-not-allowed transition whitespace-nowrap">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l4 4L19 6" />
            </svg>
            <span>Publish selected</span>
        </button>

        <button type="button" data-bulk-reschedule
            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 whitespace-nowrap">
            <svg class="h-3.5 w-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Reschedule</span>
        </button>

        <button type="button" data-bulk-disapprove
            class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition focus:outline-none dark:text-rose-400 dark:hover:bg-rose-950/50 whitespace-nowrap">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            <span>Disapprove</span>
        </button>

        <button type="button" data-clear-selection
            title="Clear selection" aria-label="Clear selection"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg p-0 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition dark:hover:bg-slate-800 dark:hover:text-slate-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="sr-only">Clear selection</span>
        </button>
    </form>
    <form method="POST" action="{{ route('admin.product-approvals.publish-scheduled-now') }}" class="border-t border-slate-200 px-4 py-2 dark:border-slate-700">
        @csrf
        <button name="publish_scope" value="all" class="text-xs font-medium text-slate-600 hover:underline dark:text-slate-300" onclick="return confirm('Publish all scheduled products immediately?')">Publish all scheduled now</button>
    </form>
    <dialog aria-labelledby="approval-confirm-title" aria-describedby="approval-confirm-description" data-confirm-dialog class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <h2 id="approval-confirm-title" class="text-lg font-semibold">Disapprove products?</h2>
        <p id="approval-confirm-description" data-confirm-description class="mt-2 text-sm text-slate-600 dark:text-slate-300"></p>
        <div class="mt-6 flex justify-end gap-3"><button type="button" data-dialog-cancel class="rounded-lg border border-slate-300 px-4 py-2 dark:border-slate-600">Cancel</button><button type="button" data-dialog-confirm class="rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700">Disapprove</button></div>
    </dialog>
    <dialog aria-labelledby="approval-reschedule-title" data-reschedule-dialog class="w-full max-w-md rounded-xl bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/60 dark:bg-slate-900 dark:text-slate-100">
        <form data-reschedule-dialog-form>
            <h2 id="approval-reschedule-title" class="text-lg font-semibold">Reschedule selected products</h2>
            <label class="mt-4 block text-sm">Publish date (UTC)<input name="date" type="date" required class="mt-2 w-full rounded-lg border-slate-300 bg-transparent dark:border-slate-600"></label>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Uses the configured launch time. Due dates publish immediately.</p>
            <div class="mt-6 flex justify-end gap-3"><button type="button" data-reschedule-cancel class="rounded-lg border px-4 py-2">Cancel</button><button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Reschedule</button></div>
        </form>
    </dialog>
    <div id="approval-tooltip" data-approval-tooltip role="tooltip" class="pointer-events-none fixed z-50 max-w-sm whitespace-pre-line break-words rounded-lg bg-slate-900 px-3 py-2 text-xs leading-5 text-white shadow-xl" hidden></div>
</section>
