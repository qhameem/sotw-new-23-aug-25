@php
    $logoUrl = $product->logo
        ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo))
        : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link);
    $previewUrl = route('admin.product-approvals.preview', $product);
    $pendingCustomCategoryCount = $product->pending_custom_category_submissions_count;
    $isBadgeSubmission = $product->submission_type === 'badge';
    $submissionLabel = ['badge' => 'Badge launch', 'paid' => 'Paid launch', 'free' => 'Free submission'][$product->submission_type] ?? 'Unknown source';
    $productDomain = parse_url($product->link, PHP_URL_HOST) ?: $product->link;
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
                        form="bulk-approve-form"
                        @disabled($pendingCustomCategoryCount > 0)
                        title="{{ $pendingCustomCategoryCount > 0 ? 'Complete custom category details before bulk approval' : 'Select for bulk approval' }}">
                    Select
                </label>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="rounded-full bg-slate-100 px-2 py-1 font-medium text-slate-700">#{{ $product->id }}</span>
                <span @class([
                    'rounded-full px-2 py-1 font-semibold',
                    'bg-emerald-50 text-emerald-700' => $isBadgeSubmission && $product->badge_verified,
                    'bg-amber-50 text-amber-700' => $isBadgeSubmission && !$product->badge_verified,
                    'bg-sky-50 text-sky-700' => !$isBadgeSubmission,
                ])>{{ $submissionLabel }}@if($isBadgeSubmission) · {{ $product->badge_verified ? 'Verified' : 'Not verified' }}@endif</span>
                @if($product->generation_review_required)
                    <span class="rounded-full bg-violet-50 px-2 py-1 font-semibold text-violet-700">Generated copy needs review</span>
                @endif
                <span>Submitted {{ $product->created_at->format('d M Y, H:i') }}</span>
                <span>{{ $productDomain }}</span>

                @foreach($product->categories->take(3) as $category)
                    <span class="rounded-full bg-slate-100 px-2 py-1 font-medium text-slate-700">{{ $category->name }}</span>
                @endforeach

                @if($product->categories->count() > 3)
                    <span>+{{ $product->categories->count() - 3 }} more</span>
                @endif

                @if($pendingCustomCategoryCount > 0)
                    <button type="button"
                        class="pointer-events-auto relative z-10 rounded-full bg-amber-50 px-2 py-1 font-medium text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                        data-custom-category-open="{{ $product->id }}">
                        {{ $pendingCustomCategoryCount }} custom {{ Str::plural('category', $pendingCustomCategoryCount) }}
                    </button>
                @endif
            </div>

            <div class="mt-3 grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 sm:grid-cols-2 lg:grid-cols-3">
                <div><span class="block font-medium text-slate-500">Submitter</span><span class="mt-0.5 block break-all font-semibold text-slate-800">{{ $product->user->name ?? 'Unknown' }} · {{ $product->user->email ?? 'No email' }}</span></div>
                <div><span class="block font-medium text-slate-500">Submission method</span><span class="mt-0.5 block font-semibold text-slate-800">{{ $submissionLabel }}</span></div>
                @if($isBadgeSubmission)
                    <div><span class="block font-medium text-slate-500">Badge page</span><a href="{{ $product->badge_placement_url ?: $product->link }}" target="_blank" rel="noopener nofollow" class="pointer-events-auto relative z-10 mt-0.5 block truncate font-semibold text-sky-700 hover:underline">{{ $product->badge_placement_url ?: $product->link }}</a></div>
                @elseif($product->published_at)
                    <div><span class="block font-medium text-slate-500">Requested publish date</span><span class="mt-0.5 block font-semibold text-slate-800">{{ $product->published_at->copy()->timezone('UTC')->format('d M Y, H:i') }} UTC</span></div>
                @endif
            </div>
        </div>

    </div>

    <div class="relative z-10 mt-3 flex flex-wrap items-end justify-end gap-2">
        @include('admin.product_approvals._delete_form')
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
        <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="js-publish-approval-form" data-product-id="{{ $product->id }}" data-has-pending-categories="{{ $pendingCustomCategoryCount > 0 ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="publish_option" value="now">
            <button type="submit" class="js-loading-button inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-70">
                <svg class="js-button-icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l4 4L19 6" /></svg>
                <svg class="js-button-spinner js-publish-spinner hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span class="js-publish-label">Publish now</span>
            </button>
        </form>
        <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="js-publish-approval-form" data-product-id="{{ $product->id }}" data-has-pending-categories="{{ $pendingCustomCategoryCount > 0 ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="publish_option" value="specific_date">
            <input type="hidden" name="published_at" value="">
            <button type="submit" class="js-loading-button inline-flex items-center gap-2 rounded-lg bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-wait disabled:opacity-70">
                <svg class="js-button-icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                <svg class="js-button-spinner js-publish-spinner hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span class="js-publish-label">Publish on selected date</span>
            </button>
        </form>
    </div>
</article>

@if($pendingCustomCategoryCount > 0)
    <div id="custom-category-modal-{{ $product->id }}" class="custom-category-modal" role="dialog" aria-modal="true" aria-labelledby="custom-category-title-{{ $product->id }}">
        <div class="custom-category-modal-panel w-full max-w-2xl rounded-xl bg-white shadow-xl">
            <div class="custom-category-modal-header flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4">
                <div>
                    <h2 id="custom-category-title-{{ $product->id }}" class="font-semibold text-slate-900">Complete custom category details</h2>
                    <p class="mt-1 text-slate-600">Add hub-page copy for each category before publishing {{ $product->name }}.</p>
                </div>
                <button type="button" data-custom-category-close class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close modal">&times;</button>
            </div>

            <div class="custom-category-modal-body space-y-3 px-5 py-4">
                @foreach($product->customCategorySubmissions as $submission)
                    <form class="js-custom-category-form rounded-xl border border-slate-200 p-4" action="{{ route('admin.product-approvals.approve-custom-category', [$product, $submission]) }}" data-product-id="{{ $product->id }}">
                        @csrf
                        <input type="text" name="name" value="{{ $submission->name }}" required maxlength="255" readonly aria-label="Category name" title="Click to edit category name" class="custom-category-name js-custom-category-name block w-full cursor-pointer rounded-lg border-transparent bg-transparent font-semibold text-slate-900 focus:cursor-text focus:border-sky-500 focus:ring-sky-500">
                        <div class="mt-1 text-xs uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', $submission->type) }}</div>
                        <input type="hidden" name="slug" value="{{ Str::slug($submission->name) }}">
                        <div class="mt-3">
                            <div class="flex items-center justify-between gap-3">
                                <label for="custom-category-description-{{ $submission->id }}" class="font-medium text-slate-700">Hub page description</label>
                                <button type="button" class="js-generate-category-copy inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800 disabled:cursor-wait disabled:opacity-50">
                                    <svg class="js-ai-icon h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    <svg class="js-ai-spinner hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                    <span class="js-ai-label">Generate with AI</span>
                                </button>
                            </div>
                            <textarea id="custom-category-description-{{ $submission->id }}" name="description" rows="3" required data-character-count data-min-length="160" data-max-length="300" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"></textarea>
                            <p class="js-character-status mt-1 text-right text-xs text-amber-600" aria-live="polite">0 characters · Too low (recommended 160–300)</p>
                        </div>
                        <div class="mt-3">
                            <label for="custom-category-meta-{{ $submission->id }}" class="font-medium text-slate-700">Meta description</label>
                            <textarea id="custom-category-meta-{{ $submission->id }}" name="meta_description" rows="2" maxlength="255" required data-character-count data-min-length="140" data-max-length="155" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"></textarea>
                            <p class="js-character-status mt-1 text-right text-xs text-amber-600" aria-live="polite">0 characters · Too low (recommended 140–155)</p>
                        </div>
                        <div class="js-custom-category-error mt-3 hidden text-sm text-red-600"></div>
                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="js-save-custom-category custom-category-individual-save">Save this category</button>
                        </div>
                        <div class="js-ai-terminal mt-4 hidden overflow-hidden rounded-lg border border-slate-700 bg-slate-950 font-mono text-xs text-slate-200" aria-live="polite">
                            <div class="flex items-center justify-between border-b border-slate-700 px-3 py-2 text-slate-400">
                                <span>AI generation process</span>
                                <span class="js-ai-terminal-state">Running</span>
                            </div>
                            <div class="js-ai-terminal-output max-h-44 space-y-1 overflow-y-auto p-3"></div>
                        </div>
                    </form>
                @endforeach
            </div>

            <div class="custom-category-modal-footer flex items-center justify-end gap-3 border-t border-slate-200 bg-white px-5 py-3">
                <button type="button" data-custom-category-close class="custom-category-cancel-button">Cancel</button>
                <button type="button" class="js-save-all-custom-categories custom-category-save-button" data-product-id="{{ $product->id }}">Save categories</button>
            </div>
        </div>
    </div>
@endif
