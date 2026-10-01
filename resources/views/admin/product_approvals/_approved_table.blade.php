@php
    $groups = $approvedProducts->getCollection()->groupBy(fn ($product) => !$product->is_published && $product->published_at
        ? $product->published_at->copy()->timezone('UTC')->toDateString() : 'published');
    $filterParams = ['search' => $search, 'per_page' => $perPage, 'sort_by' => 'published_at', 'sort_direction' => $sortDirection];
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
    <form method="GET" action="{{ route('admin.product-approvals.index') }}" class="flex flex-wrap items-center gap-3 px-4 py-3">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="hidden" name="sort_by" value="published_at">
        <label class="min-w-48 flex-1"><span class="sr-only">Search products or submitters</span><input type="search" name="search" value="{{ $search }}" placeholder="Search products or submitters…" class="w-full rounded-lg border-slate-300 bg-transparent py-2 text-sm placeholder:text-slate-500 dark:border-slate-600"></label>
        <label class="flex items-center gap-2">Publish date
            <select name="sort_direction" class="rounded-lg border-slate-300 py-2 text-sm dark:border-slate-600 dark:bg-slate-900" onchange="this.form.requestSubmit()">
                <option value="asc" @selected($sortDirection === 'asc')>Earliest first</option><option value="desc" @selected($sortDirection === 'desc')>Latest first</option>
            </select>
        </label>
        <label class="flex items-center gap-2">Rows
            <select name="per_page" class="rounded-lg border-slate-300 py-2 text-sm dark:border-slate-600 dark:bg-slate-900" onchange="this.form.requestSubmit()">
                @foreach([20, 50, 100] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach
            </select>
        </label>
        <button class="rounded-lg bg-slate-900 px-3 py-2 font-medium text-white dark:bg-slate-100 dark:text-slate-900">Search</button>
    </form>
    <div class="max-h-[70vh] overflow-auto">
        <table class="w-full min-w-[1120px] table-fixed text-left text-sm">
            <colgroup><col class="w-[27%]"><col class="w-[12%]"><col class="w-[18%]"><col class="w-[13%]"><col class="w-[9%]"><col class="w-[9%]"><col class="w-[12%]"></colgroup>
            <thead class="sticky top-0 z-10 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <tr>
                    <th scope="col" class="px-4 py-3"><div class="flex items-center gap-3"><input type="checkbox" data-select-page aria-label="Select all products on this page" class="h-4 w-4 rounded border-slate-400 text-indigo-600">Product</div></th>
                    @foreach(['Submitter', 'Categories', 'Publish date', 'Status', 'Badge', 'Actions'] as $heading)<th scope="col" class="px-3 py-3 font-semibold">{{ $heading }}</th>@endforeach
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
    <form method="POST" action="{{ route('admin.product-approvals.bulk-manage') }}" data-bulk-form class="approval-bulk sticky bottom-4 z-20 mx-4 my-3 flex flex-wrap items-center gap-3 rounded-xl bg-slate-900 px-4 py-3 text-white shadow-lg dark:bg-indigo-950" hidden>
        @csrf
        <span data-selection-count class="mr-auto font-semibold" aria-live="polite"></span>
        <div data-selected-inputs></div>
        <button type="submit" formaction="{{ route('admin.product-approvals.publish-scheduled-now') }}" name="publish_scope" value="selected" class="rounded-lg bg-emerald-600 px-3 py-2 font-medium hover:bg-emerald-500">Publish selected</button>
        <button type="button" data-bulk-reschedule class="rounded-lg border border-slate-500 px-3 py-2 hover:bg-slate-800">Reschedule</button>
        <button type="button" data-bulk-disapprove class="rounded-lg px-3 py-2 text-red-300 hover:bg-red-950">Disapprove</button>
        <button type="button" data-clear-selection class="rounded-lg px-2 py-2" aria-label="Clear selection">✕</button>
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
    <div id="approval-tooltip" data-approval-tooltip role="tooltip" class="fixed z-50 max-w-sm whitespace-pre-line rounded-lg bg-slate-950 px-3 py-2 text-xs leading-5 text-white shadow-xl" hidden></div>
</section>
