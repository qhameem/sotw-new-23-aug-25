@php
    $mainContentMaxWidth = 'max-w-7xl';
    $title = '';
    $mainPadding = 'px-4 sm:px-6 lg:px-10 xl:px-12';
    $headerPadding = 'px-4 sm:px-6 lg:px-10 xl:px-12';
@endphp
@extends('layouts.app')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)
@if($product->approved && $product->is_published && !$product->generation_noindex)
    @push('alternate-formats')
        <link rel="alternate" type="text/markdown" href="{{ route('products.document', ['slug' => $product->slug, 'format' => 'md']) }}">
        <link rel="alternate" type="application/json" href="{{ route('products.document', ['slug' => $product->slug, 'format' => 'json']) }}">
    @endpush
@endif
@if(!empty($isUnpublishedProduct) || $product->generation_noindex)
    @section('robots', 'noindex, nofollow, noarchive')
@endif

@push('styles')
    @vite(['resources/css/rich-content.css', 'resources/css/product-page.css'])
@endpush

@section('content')
    @php
        $appendQueryParam = function (?string $url, string $key, string $value): ?string {
            if (!filled($url)) {
                return $url;
            }

            $fragment = null;
            if (str_contains($url, '#')) {
                [$url, $fragment] = explode('#', $url, 2);
            }

            $separator = str_contains($url, '?') ? '&' : '?';
            $updatedUrl = $url . $separator . urlencode($key) . '=' . urlencode($value);

            return $fragment !== null ? $updatedUrl . '#' . $fragment : $updatedUrl;
        };
        $makerLinks = collect(is_array($product->maker_links) ? $product->maker_links : json_decode($product->maker_links, true) ?? [])
            ->filter(fn ($link) => filled($link))
            ->values();
        $xProfileUrl = \App\Models\Product::xProfileUrl($product->x_account);
        $normalizedXProfileUrl = $xProfileUrl ? \App\Models\Product::normalizeLink($xProfileUrl) : null;
        $hasResourcesSection = $xProfileUrl || $makerLinks->contains(function ($link) use ($normalizedXProfileUrl) {
            return !$normalizedXProfileUrl || \App\Models\Product::normalizeLink($link) !== $normalizedXProfileUrl;
        });
        $pageSections = app(\App\Services\ProductPageSections::class)->build($product, $productEditorial, $descriptionContent);
        $productFaqItems = collect($product->faq ?: ($productEditorial['faq'] ?? []))
            ->map(function ($item) {
                $question = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($item['question'] ?? ''))));
                $answer = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($item['answer'] ?? ''))));

                if ($question === '' || $answer === '') {
                    return null;
                }

                return [
                    'question' => $question,
                    'answer' => \Illuminate\Support\Str::words($answer, 100, '…'),
                ];
            })
            ->filter()
            ->unique('question')
            ->take(6)
            ->values();
    @endphp
    @if(empty($isUnpublishedProduct))
        @include('products.partials._json-ld-product', ['productFaqItems' => $productFaqItems])
    @endif
    <div id="product-detail-metrics" data-product-id="{{ $product->id }}" hidden></div>
    <div class="pt-2 pb-4">
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>

    @if(!empty($isUnpublishedProduct))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            This product has not been published yet. It is visible here for preview only and may still change before going live.
        </div>
    @endif

    <div class="product-page pt-2 pb-6 md:pt-3 md:pb-8"
        x-data="{
            @if(isset($isAdminView) && $isAdminView)
                editingName: false,
                editingTagline: false,
                editingProductPageTagline: false,
                editingDescription: false,
                editingCategories: false,
                editingLogo: false,
                editingVideoUrl: false,
                saveError: '',
                name: {{ Js::from($product->name) }},
                tagline: {{ Js::from($product->tagline) }},
                product_page_tagline: {{ Js::from($product->product_page_tagline) }},
                description: {{ Js::from($product->description) }},
                category_ids: {{ Js::from($product->categories->pluck('id')->all()) }},
                video_url: {{ Js::from($product->video_url) }},
                async updateProduct() {
                    this.saveError = '';
                    const formData = new FormData();
                    for (const key of ['name', 'tagline', 'product_page_tagline', 'description', 'video_url']) {
                        formData.append(key, this[key] || '');
                    }
                    this.category_ids.forEach(id => formData.append('categories[]', id));
                    const logoInput = this.$el.querySelector('input[type=file]');
                    if (logoInput?.files?.[0]) formData.append('logo', logoInput.files[0]);
                    try {
                        const response = await fetch({{ Js::from(route('admin.products.update', $product)) }}, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': {{ Js::from(csrf_token()) }}, 'X-HTTP-Method-Override': 'PUT', 'Accept': 'application/json' },
                            body: formData
                        });
                        if (response.ok) window.location.reload();
                        else this.saveError = 'Product could not be saved. Check required fields or use the full editor.';
                    } catch (error) {
                        this.saveError = 'Product could not be saved. Check your connection.';
                    }
                }
            @endif
        }">
        @if(isset($isAdminView) && $isAdminView)
            <p x-show="saveError" x-text="saveError" role="alert" class="mb-4 text-sm text-red-700" x-cloak></p>
        @endif
        <div class="product-sheet overflow-hidden rounded-lg">
            <div class="px-4 py-6 sm:px-6">
                @include('products.partials._hero')
            </div>
            @include('products.partials._structured-content')
            @if(!empty($weekNavigation))
                <div class="border-t border-gray-100 px-4 pb-6 sm:px-6">
                    @include('products.partials._week-navigation', ['weekNavigation' => $weekNavigation])
                </div>
            @endif
            <div class="border-t border-gray-100 p-4 md:hidden">
                @include('products.partials._sidebar-info')
            </div>
            @if(isset($isAdminView) && $isAdminView)
                <div class="border-t border-gray-100 p-4">
                    <h2 class="mb-2 text-sm font-semibold">Edit categories and video</h2>
                    <label for="admin-product-video" class="text-xs">Video URL</label>
                    <input id="admin-product-video" type="url" x-model="video_url" class="form-input mb-3 block w-full">
                    <label for="admin-product-categories" class="text-xs">Categories</label>
                    <select id="admin-product-categories" multiple x-model="category_ids" class="form-multiselect block w-full">
                        @foreach($allCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>
                    <button type="button" @click="updateProduct()" class="mt-3 text-sm font-semibold">Save</button>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('right_sidebar_content')
    <div class="hidden md:block space-y-6 md:pt-6">
        @include('partials._sidebar-ads')
        @include('products.partials._featured-alternatives-sidebar', ['alternativeProducts' => $alternativeProducts])
        @include('products.partials._sidebar-info')
    </div>
@endsection

@if($product->user->hasRole('admin') && !(Auth::check() && Auth::user()->hasRole('admin')))
    @include('products.partials._admin-product-claim-modal', ['product' => $product])
@endif

@auth
    @include('products.partials._save-to-collections-modal', ['product' => $product])
@endauth

<template class="delayed-body-snippet">
    <script src="https://app.tinyadz.com/scripts/v1.0/ads.js" data-site-id="689a2b0d06e074933a271e16" async></script>
</template>
