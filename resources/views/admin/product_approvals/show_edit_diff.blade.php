@extends('layouts.app')

@section('content')
    @php
        $currentScreenshot = $product->media->whereIn('type', ['image', 'screenshot'])->sortBy('id')->first();
        $pendingCustomSubmissions = $product->customCategorySubmissions->where('status', 'pending')->values();
        $currentDescriptionHtml = \App\Support\OutboundLink::sanitizeHtml($product->description, 'product_description');
        $proposedDescriptionHtml = \App\Support\OutboundLink::sanitizeHtml($product->proposed_description, 'product_description');
    @endphp
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 mb-1">Review Edits for: {{ $product->name }}</h1>
                <div class="flex flex-col space-y-1">
                    <p class="text-sm text-gray-600">Submitted by: <span
                            class="font-medium text-gray-900">{{ $product->user->name ?? 'N/A' }}</span> on
                        {{ $product->created_at->format('M d, Y') }}</p>
                    @if($product->lastEditor)
                        <p class="text-sm text-blue-600 font-semibold">Last edited by: <span
                                class="text-blue-800">{{ $product->lastEditor->name }}</span>
                            ({{ $product->updated_at->diffForHumans() }})</p>
                    @endif
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.products.pending-edits.index') }}"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Back to List
                </a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 divide-x divide-gray-200">
                {{-- Current Live Version --}}
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 uppercase tracking-tight">
                        Current Live Version</h2>
                    <div class="space-y-6">
                        {{-- Logo --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Logo</label>
                            <div class="mt-1">
                                @if($product->logo)
                                    <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                                        alt="Current Logo" class="w-20 h-20 object-contain rounded border bg-gray-50">
                                @else
                                    <span class="text-gray-400 italic text-sm">No logo</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Primary Screenshot</label>
                            <div class="mt-1">
                                @if($currentScreenshot)
                                    <img src="{{ asset('storage/' . ($currentScreenshot->path_medium ?? $currentScreenshot->path)) }}"
                                        alt="Current screenshot" class="w-full max-w-sm rounded border bg-gray-50">
                                @else
                                    <span class="text-gray-400 italic text-sm">No screenshot</span>
                                @endif
                            </div>
                        </div>

                        {{-- Basic Info --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Name</label>
                                <p class="text-sm text-gray-900 font-medium">{{ $product->name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Slug</label>
                                <p class="text-sm text-gray-500">{{ $product->slug }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline
                                    (Listing)</label>
                                <p class="text-sm text-gray-900">{{ $product->tagline }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline (Product
                                    Page)</label>
                                <p class="text-sm text-gray-900">{{ $product->product_page_tagline ?? 'Not set' }}</p>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Description</label>
                            <div class="mt-1 text-sm text-gray-700 prose prose-sm max-w-none bg-gray-50 p-3 rounded border">
                                {!! $currentDescriptionHtml !!}
                            </div>
                        </div>

                        {{-- Links & More --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Product Link</label>
                                <p class="text-sm text-blue-600 break-all"><a href="{{ $product->link }}" target="_blank" rel="{{ \App\Support\OutboundLink::rel($product->link, 'product_link') }}"
                                        class="hover:underline">{{ $product->link }}</a></p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Video URL</label>
                                <p class="text-sm text-gray-900 break-all">{{ $product->video_url ?? 'None' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">X Account</label>
                                <p class="text-sm text-gray-900">@ {{ $product->x_account ?? 'None' }}</p>
                            </div>
                        </div>

                        {{-- Categories & Tech --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Categories</label>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @forelse($product->categories as $category)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">{{ $category->name }}</span>
                                    @empty
                                        <span class="text-gray-400 italic text-sm">No categories</span>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tech Stacks</label>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @forelse($product->techStacks as $tech)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">{{ $tech->name }}</span>
                                    @empty
                                        <span class="text-gray-400 italic text-sm">None</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Selling Info --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Selling Status</label>
                            <p class="text-sm text-gray-900">
                                {{ $product->sell_product ? 'For Sale' : 'Not for Sale' }}
                                @if($product->sell_product && $product->asking_price)
                                    <span
                                        class="ml-2 font-bold text-green-600">${{ number_format($product->asking_price, 2) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Proposed Edits --}}
                <div class="p-6 bg-yellow-50/30">
                    <h2
                        class="text-lg font-bold text-yellow-700 mb-4 pb-2 border-b border-yellow-200 uppercase tracking-tight">
                        Proposed Edits (Awaiting Approval)</h2>
                    <div class="space-y-6">
                        {{-- Proposed Logo --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Logo</label>
                            <div class="mt-1">
                                @if($product->proposed_logo_path)
                                    <img src="{{ asset('storage/' . $product->proposed_logo_path) }}" alt="Proposed Logo"
                                        class="w-20 h-20 object-contain rounded border border-yellow-400 bg-white">
                                    <p class="mt-1 text-[0.7rem] text-green-600 font-bold">New logo proposed</p>
                                @elseif(is_null($product->proposed_logo_path) && $product->logo && $product->has_pending_edits)
                                    <div class="p-2 border border-red-200 bg-red-50 rounded text-red-600 text-sm font-semibold">
                                        Propose to REMOVE logo</div>
                                @else
                                    <span class="text-gray-400 italic text-sm">No change proposed</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Primary Screenshot</label>
                            <div class="mt-1">
                                @if($product->proposed_screenshot_path)
                                    <img src="{{ asset('storage/' . ($product->proposed_screenshot_medium_path ?? $product->proposed_screenshot_path)) }}"
                                        alt="Proposed screenshot" class="w-full max-w-sm rounded border border-yellow-300 bg-white">
                                    <p class="mt-1 text-[0.7rem] text-green-600 font-bold">New screenshot proposed</p>
                                @else
                                    <span class="text-gray-400 italic text-sm">No change proposed</span>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Basic Info --}}
                        <div class="grid grid-cols-1 gap-4">
                            {{-- Name --}}
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Name</label>
                                @if($product->proposed_name && $product->proposed_name !== $product->name)
                                    <p class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        {{ $product->proposed_name }}</p>
                                    <p class="text-[0.65rem] text-blue-600 mt-1 uppercase font-bold tracking-tight">* Slug will
                                        be updated on approval</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                            {{-- Tagline --}}
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline
                                    (Listing)</label>
                                @if($product->proposed_tagline && $product->proposed_tagline !== $product->tagline)
                                    <p class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        {{ $product->proposed_tagline }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                            {{-- Product Page Tagline --}}
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline (Product
                                    Page)</label>
                                @if($product->proposed_product_page_tagline && $product->proposed_product_page_tagline !== $product->product_page_tagline)
                                    <p class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        {{ $product->proposed_product_page_tagline }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Description --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Description</label>
                            @if($product->proposed_description && $product->proposed_description !== $product->description)
                                <div
                                    class="mt-1 text-sm text-gray-900 prose prose-sm max-w-none bg-yellow-100 p-3 rounded border border-yellow-200">
                                    {!! $proposedDescriptionHtml !!}
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                            @endif
                        </div>

                        {{-- Proposed Links --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Product Link</label>
                                @if($product->proposed_link && $product->proposed_link !== $product->link)
                                    <p
                                        class="text-sm text-blue-800 bg-yellow-100 p-2 rounded border border-yellow-200 break-all">
                                        {{ $product->proposed_link }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Video URL</label>
                                @if($product->proposed_video_url && $product->proposed_video_url !== $product->video_url)
                                    <p
                                        class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200 break-all">
                                        {{ $product->proposed_video_url }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Categories & Tech --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Categories</label>
                                @php
                                    $currentCatIds = $product->categories->pluck('id')->sort()->values()->toArray();
                                    $proposedCatIds = $product->proposedCategories->pluck('id')->sort()->values()->toArray();
                                    $hasCatChanges = $currentCatIds !== $proposedCatIds;
                                @endphp

                                @if($hasCatChanges)
                                    <div class="mt-1 flex flex-wrap gap-1 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        @forelse($product->proposedCategories as $category)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-200 text-yellow-800 border border-yellow-300">{{ $category->name }}</span>
                                        @empty
                                            <span class="text-red-600 italic text-sm">Propose to remove all categories</span>
                                        @endforelse
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                                @endif
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tech Stacks</label>
                                @php
                                    $currentTechIds = $product->techStacks->pluck('id')->sort()->values()->toArray();
                                    $proposedTechIds = $product->proposedTechStacks->pluck('id')->sort()->values()->toArray();
                                    $hasTechChanges = $currentTechIds !== $proposedTechIds;
                                @endphp

                                @if($hasTechChanges)
                                    <div class="mt-1 flex flex-wrap gap-1 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        @forelse($product->proposedTechStacks as $tech)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-200 text-yellow-800 border border-yellow-300">{{ $tech->name }}</span>
                                        @empty
                                            <span class="text-red-600 italic text-sm">Propose to remove all tech stacks</span>
                                        @endforelse
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Selling Info --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Selling Status &
                                Price</label>
                            @php
                                $hasPriceChanges = !is_null($product->proposed_sell_product) && ($product->proposed_sell_product !== $product->sell_product || $product->proposed_asking_price != $product->asking_price);
                            @endphp
                            @if($hasPriceChanges)
                                <div class="mt-1 bg-yellow-100 p-2 rounded border border-yellow-200">
                                    <p class="text-sm text-gray-900 font-bold">
                                        {{ $product->proposed_sell_product ? 'For Sale' : 'Not for Sale' }}
                                        @if($product->proposed_sell_product && $product->proposed_asking_price)
                                            <span
                                                class="ml-2 font-black text-green-700">${{ number_format($product->proposed_asking_price, 2) }}</span>
                                        @endif
                                    </p>
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-200 bg-gray-50 p-6">
                @if($pendingCustomSubmissions->isNotEmpty())
                    <div class="mb-6">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-600">Pending Custom Submissions</h3>
                        <p class="mt-1 text-sm text-gray-500">You can approve or reject any custom use case, category, platform, best-for tag, or tech stack while approving these edits.</p>
                    </div>
                @endif

                <form id="approve-edits-form" action="{{ route('admin.products.approve-edits', $product) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to approve these edits? The live product will be updated with all yellow-highlighted values.');">
                    @csrf

                    @if($pendingCustomSubmissions->isNotEmpty())
                        <div class="mb-6 space-y-4">
                            @foreach($pendingCustomSubmissions as $submission)
                                <div class="rounded-lg border border-gray-200 bg-white p-4">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $submission->name }}</p>
                                            <p class="text-xs uppercase tracking-wider text-gray-500">{{ str_replace('_', ' ', $submission->type) }}</p>
                                        </div>
                                        <select name="custom_category_{{ $submission->id }}"
                                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 js-custom-category-decision"
                                            data-submission-id="{{ $submission->id }}">
                                            <option value="">Leave pending</option>
                                            <option value="approve">Approve</option>
                                            <option value="reject">Reject</option>
                                        </select>
                                    </div>

                                    <div class="mt-4 hidden rounded-md border border-sky-100 bg-sky-50 p-4 js-custom-category-fields"
                                        id="custom-category-fields-{{ $submission->id }}">
                                        <div class="grid gap-4">
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-600">Slug</label>
                                                <input type="text" name="custom_category_{{ $submission->id }}_slug"
                                                    value="{{ Str::slug($submission->name) }}"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                            </div>
                                            <div>
                                                <div class="mb-1 flex items-center justify-between gap-3">
                                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600">Description</label>
                                                    <button type="button"
                                                        class="js-generate-ai-seo inline-flex items-center gap-1 text-xs font-medium text-indigo-600 transition hover:text-indigo-800"
                                                        data-submission-id="{{ $submission->id }}"
                                                        data-category-name="{{ $submission->name }}"
                                                        data-category-type="{{ $submission->type }}">
                                                        <span class="icon-default">
                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                            </svg>
                                                        </span>
                                                        <span class="icon-loading hidden">
                                                            <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                        </span>
                                                        <span class="btn-text">Generate via AI</span>
                                                    </button>
                                                </div>
                                                <textarea name="custom_category_{{ $submission->id }}_description" rows="2"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ $submission->name }}</textarea>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-600">Meta Description</label>
                                                <textarea name="custom_category_{{ $submission->id }}_meta_description" rows="2"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ $submission->name }}</textarea>
                                            </div>
                                            <div>
                                                <button type="button"
                                                    data-submission-id="{{ $submission->id }}"
                                                    data-product-id="{{ $product->id }}"
                                                    class="js-save-custom-category inline-flex w-full items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                                                    Save Category
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-4 font-semibold">
                        <button type="submit"
                            class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none transition-colors">
                            Approve & Apply All Edits
                        </button>
                    </div>
                </form>

                <div class="mt-4 flex items-center justify-end gap-4 font-semibold">
                    <form action="{{ route('admin.products.reject-edits', $product) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to reject these edits? All yellow-highlighted changes will be permanently discarded.');">
                        @csrf
                        <button type="submit"
                            class="px-6 py-2 border border-red-300 rounded-lg shadow-sm text-sm font-bold text-red-700 bg-white hover:bg-red-50 focus:outline-none transition-colors">
                            Reject All Proposed Edits
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-custom-category-decision').forEach((select) => {
                select.addEventListener('change', function () {
                    const fields = document.getElementById(`custom-category-fields-${this.dataset.submissionId}`);
                    if (!fields) {
                        return;
                    }

                    if (this.value === 'approve') {
                        fields.classList.remove('hidden');
                    } else {
                        fields.classList.add('hidden');
                    }
                });
            });

            document.querySelectorAll('.js-save-custom-category').forEach((button) => {
                button.addEventListener('click', async function () {
                    const submissionId = this.dataset.submissionId;
                    const productId = this.dataset.productId;
                    const slugInput = document.querySelector(`input[name="custom_category_${submissionId}_slug"]`);
                    const descInput = document.querySelector(`textarea[name="custom_category_${submissionId}_description"]`);
                    const metaDescInput = document.querySelector(`textarea[name="custom_category_${submissionId}_meta_description"]`);

                    if (!slugInput || !slugInput.value.trim()) {
                        alert('Slug is required to save the category.');
                        return;
                    }

                    const originalText = this.textContent;
                    this.textContent = 'Saving...';
                    this.disabled = true;

                    try {
                        const response = await fetch(`/admin/product-approvals/${productId}/approve-custom-category/${submissionId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                slug: slugInput.value.trim(),
                                description: descInput ? descInput.value.trim() : null,
                                meta_description: metaDescInput ? metaDescInput.value.trim() : null,
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            const container = this.closest('.rounded-lg.border');
                            if (container) {
                                container.innerHTML = `<div class="flex items-center gap-2 text-sm font-medium text-green-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Custom category approved and saved successfully.</div>`;
                            }
                        } else {
                            alert(data.message || 'Failed to save custom category.');
                            this.textContent = originalText;
                            this.disabled = false;
                        }
                    } catch (error) {
                        console.error('Error saving custom category:', error);
                        alert('An error occurred while saving.');
                        this.textContent = originalText;
                        this.disabled = false;
                    }
                });
            });

            document.querySelectorAll('.js-generate-ai-seo').forEach((button) => {
                button.addEventListener('click', async function () {
                    const submissionId = this.dataset.submissionId;
                    const categoryName = this.dataset.categoryName;
                    const categoryType = this.dataset.categoryType || '';
                    const descInput = document.querySelector(`textarea[name="custom_category_${submissionId}_description"]`);
                    const metaDescInput = document.querySelector(`textarea[name="custom_category_${submissionId}_meta_description"]`);
                    const defaultIcon = this.querySelector('.icon-default');
                    const loadingIcon = this.querySelector('.icon-loading');
                    const btnText = this.querySelector('.btn-text');

                    defaultIcon?.classList.add('hidden');
                    loadingIcon?.classList.remove('hidden');
                    if (btnText) btnText.textContent = 'Generating...';
                    this.classList.add('opacity-50', 'cursor-not-allowed');
                    this.disabled = true;

                    try {
                        const response = await fetch(`/admin/product-approvals/generate-category-seo`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                category_name: categoryName,
                                category_type: categoryType,
                            })
                        });

                        const data = await response.json();

                        if (data.success && data.data) {
                            if (descInput) descInput.value = data.data.description;
                            if (metaDescInput) metaDescInput.value = data.data.meta_description;
                        } else {
                            alert(data.message || 'Failed to generate content.');
                        }
                    } catch (error) {
                        console.error('Error generating AI content:', error);
                        alert('An error occurred while generating content.');
                    } finally {
                        defaultIcon?.classList.remove('hidden');
                        loadingIcon?.classList.add('hidden');
                        if (btnText) btnText.textContent = 'Generate via AI';
                        this.classList.remove('opacity-50', 'cursor-not-allowed');
                        this.disabled = false;
                    }
                });
            });
        });
    </script>
@endsection
