@php
    $logoUrl = $product->logo
        ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo))
        : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link);
    $previewUrl = route('admin.product-approvals.preview', $product);
    $pendingCustomCategoryCount = $product->pending_custom_category_submissions_count;
@endphp

<article class="relative rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
    <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
        class="absolute inset-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
        aria-label="Open {{ $product->name }} product page"></a>

    <div class="pointer-events-none relative flex items-start gap-4">
        <img src="{{ $logoUrl }}" alt="{{ $product->name }} logo"
            class="h-12 w-12 flex-shrink-0 rounded-xl border border-slate-200 bg-gray-100 object-contain">

        <div class="min-w-0 flex-1">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h3 class="truncate text-base font-semibold text-slate-900">{{ $product->name }}</h3>
                    <p class="mt-1 line-clamp-1 text-sm text-slate-600">
                        {{ $product->product_page_tagline ?: $product->tagline }}
                    </p>
                </div>

                <label class="pointer-events-auto relative z-10 inline-flex flex-shrink-0 items-center gap-2 text-sm font-medium text-slate-600">
                    <input type="checkbox" name="products[]" value="{{ $product->id }}"
                        class="product-checkbox h-5 w-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500"
                        form="bulk-approve-form">
                    Select
                </label>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                <span>{{ $product->created_at->format('d M, Y') }}</span>
                <span>{{ $product->user->name ?? 'N/A' }}</span>

                @foreach($product->categories->take(3) as $category)
                    <span class="rounded-full bg-slate-100 px-2 py-1 font-medium text-slate-700">{{ $category->name }}</span>
                @endforeach

                @if($product->categories->count() > 3)
                    <span>+{{ $product->categories->count() - 3 }} more</span>
                @endif

                @if($pendingCustomCategoryCount > 0)
                    <span class="rounded-full bg-amber-50 px-2 py-1 font-medium text-amber-700">
                        {{ $pendingCustomCategoryCount }} custom {{ Str::plural('category', $pendingCustomCategoryCount) }}
                    </span>
                @endif
            </div>
        </div>

    </div>

    <div class="relative z-10 mt-3 flex flex-wrap items-end justify-end gap-2">
        <div class="mr-auto">
            <x-scheduled-datepicker name="published_at[{{ $product->id }}]" value="{{ today('UTC')->next(\Carbon\Carbon::MONDAY)->toDateString() }}" />
        </div>
        <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
            class="rounded-lg border border-sky-300 bg-white px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50">
            Preview page
        </a>
        <a href="{{ route('admin.products.edit', $product->id) }}?from=approvals"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Edit
        </a>
        <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="js-publish-approval-form" data-product-id="{{ $product->id }}">
            @csrf
            <input type="hidden" name="publish_option" value="specific_date">
            <input type="hidden" name="published_at" value="">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-sky-500 bg-white px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50">
                <svg class="js-publish-spinner hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="js-publish-label">Publish on selected date</span>
            </button>
        </form>
        <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="js-publish-approval-form" data-product-id="{{ $product->id }}">
            @csrf
            <input type="hidden" name="publish_option" value="now">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">
                <svg class="js-publish-spinner hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="js-publish-label">Publish now</span>
            </button>
        </form>
    </div>
</article>
