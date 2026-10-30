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

<article class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm transition hover:shadow-md hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-start gap-3.5">
        <div class="pt-1 flex-shrink-0">
            <input type="checkbox" name="products[]" value="{{ $product->id }}"
                class="product-checkbox h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer dark:border-slate-600 dark:bg-slate-800"
                form="bulk-approve-form"
                @disabled($pendingCustomCategoryCount > 0)
                title="{{ $pendingCustomCategoryCount > 0 ? 'Complete custom category details before bulk approval' : 'Select for bulk approval' }}"
                aria-label="Select {{ $product->name }} for bulk approval">
        </div>

        <img src="{{ $logoUrl }}" alt="{{ $product->name }} logo"
            class="h-12 w-12 flex-shrink-0 rounded-xl border border-slate-200 bg-slate-50 object-contain shadow-2xs dark:border-slate-700 dark:bg-slate-800">

        <div class="min-w-0 flex-1">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="truncate text-base font-bold text-slate-900 dark:text-slate-100">{{ $product->name }}</h3>
                        <span class="text-xs font-mono text-slate-400 dark:text-slate-500">#{{ $product->id }}</span>
                    </div>
                    <p class="mt-0.5 line-clamp-1 text-sm text-slate-600 dark:text-slate-300">
                        {{ $product->product_page_tagline ?: $product->tagline }}
                    </p>
                </div>

                <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow"
                    class="inline-flex items-center gap-1 rounded-md border border-sky-100 bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-900 dark:bg-sky-950/60 dark:text-sky-300 self-start">
                    <span>{{ $productDomain }}</span>
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>

            <div class="mt-2.5 flex flex-wrap items-center gap-2 text-xs">
                <span @class([
                    'inline-flex items-center rounded-full px-2.5 py-0.5 font-medium border',
                    'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => $isBadgeSubmission && $product->badge_verified,
                    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300' => $isBadgeSubmission && !$product->badge_verified,
                    'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-300' => !$isBadgeSubmission,
                ])>{{ $submissionLabel }}@if($isBadgeSubmission) · {{ $product->badge_verified ? 'Verified' : 'Not verified' }}@endif</span>

                @if($product->generation_review_required)
                    <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-2.5 py-0.5 font-semibold text-violet-700 dark:border-violet-800 dark:bg-violet-950 dark:text-violet-300">Generated copy needs review</span>
                @endif

                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300" title="Submitter">
                    <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span>{{ $product->user->name ? $product->user->name . ' · ' : '' }}{{ $product->user->email ?? 'No email' }}</span>
                </span>

                @if($isBadgeSubmission && $product->badge_placement_url)
                    <a href="{{ $product->badge_placement_url }}" target="_blank" rel="noopener nofollow"
                        class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 font-medium text-sky-700 hover:underline dark:border-slate-700 dark:bg-slate-800 dark:text-sky-300"
                        title="Badge page">
                        <span>Badge page</span>
                        <span class="truncate max-w-[14rem]">({{ $product->badge_placement_url }})</span>
                    </a>
                @elseif($product->published_at && !$isBadgeSubmission)
                    <span class="text-slate-500 dark:text-slate-400">Req: {{ $product->published_at->copy()->timezone('UTC')->format('d M Y, H:i') }} UTC</span>
                @endif

                <span class="text-slate-400">·</span>
                <span class="text-slate-500 dark:text-slate-400">Submitted {{ $product->created_at->format('d M Y, H:i') }}</span>

                <div class="flex flex-wrap items-center gap-1.5 sm:ml-auto">
                    @foreach($product->categories->take(3) as $category)
                        <span class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $category->name }}</span>
                    @endforeach

                    @if($product->categories->count() > 3)
                        <span class="text-slate-400" title="{{ $product->categories->skip(3)->pluck('name')->implode(', ') }}">+{{ $product->categories->count() - 3 }} more</span>
                    @endif

                    @if($pendingCustomCategoryCount > 0)
                        <button type="button"
                            class="rounded-full bg-amber-50 px-2.5 py-0.5 font-medium text-amber-700 border border-amber-200 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300"
                            data-custom-category-open="{{ $product->id }}">
                            {{ $pendingCustomCategoryCount }} custom {{ Str::plural('category', $pendingCustomCategoryCount) }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
        @include('admin.product_approvals._delete_form', ['withIcon' => true, 'class' => 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-400 hover:bg-red-50 hover:text-red-600 transition dark:hover:bg-red-950 dark:hover:text-red-300'])

        <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
                title="Preview page" aria-label="Preview page"
                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span class="sr-only">Preview page</span>
            </a>
            <a href="{{ route('admin.products.edit', $product->id) }}?from=approvals"
                title="Edit product" aria-label="Edit product"
                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span class="sr-only">Edit</span>
            </a>

            <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="js-publish-approval-form" data-product-id="{{ $product->id }}" data-has-pending-categories="{{ $pendingCustomCategoryCount > 0 ? '1' : '0' }}">
                @csrf
                <input type="hidden" name="publish_option" value="now">
                <button type="submit" class="js-loading-button inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 disabled:cursor-wait disabled:opacity-70 transition dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 dark:hover:bg-emerald-900">
                    <svg class="js-button-icon h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l4 4L19 6" /></svg>
                    <svg class="js-button-spinner js-publish-spinner hidden h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    <span class="js-publish-label">Publish now</span>
                </button>
            </form>

            <span class="hidden sm:inline-block h-4 w-px bg-slate-200 dark:bg-slate-700 mx-0.5"></span>

            <div class="inline-flex items-center rounded-lg border border-sky-200 bg-sky-50/50 p-1 gap-1.5 shadow-2xs dark:border-sky-900 dark:bg-sky-950/40">
                <x-scheduled-datepicker class="!mt-0 !py-1 !text-xs !rounded-lg !border-slate-300 dark:!border-slate-700 dark:!bg-slate-800" name="published_at[{{ $product->id }}]" value="{{ today('UTC')->next(\Carbon\Carbon::MONDAY)->toDateString() }}" />

                <form action="{{ route('admin.product-approvals.approve', $product->id) }}" method="POST" class="js-publish-approval-form" data-product-id="{{ $product->id }}" data-has-pending-categories="{{ $pendingCustomCategoryCount > 0 ? '1' : '0' }}">
                    @csrf
                    <input type="hidden" name="publish_option" value="specific_date">
                    <input type="hidden" name="published_at" value="">
                    <button type="submit" class="js-loading-button inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-700 disabled:cursor-wait disabled:opacity-70 shadow-xs transition">
                        <svg class="js-button-icon h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
                        <svg class="js-button-spinner js-publish-spinner hidden h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        <span class="js-publish-label">Publish on selected date</span>
                    </button>
                </form>
            </div>
        </div>
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
