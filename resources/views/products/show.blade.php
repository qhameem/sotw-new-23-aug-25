@php 
                                    $mainContentMaxWidth = 'max-w-7xl';
    $title = '';
    $mainPadding = 'px-4 sm:px-6 lg:px-10 xl:px-12';
    $headerPadding = 'px-4 sm:px-6 lg:px-10 xl:px-12';
@endphp
@extends('layouts.app')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@push('styles')
    @vite('resources/css/rich-content.css')
@endpush

@section('content')
    @include('products.partials._json-ld-product')
    @php
        $breadcrumbs = [];

        if ($primaryBreadcrumbCategory) {
            $breadcrumbs[] = [
                'label' => $primaryBreadcrumbCategory->name,
                'link' => route('categories.show', $primaryBreadcrumbCategory->slug),
            ];
        }

        $breadcrumbs[] = ['label' => $product->name];

        $mediaAssetCount = $product->media->count() + ($product->video_url ? 1 : 0);
        $hasMediaSection = $mediaAssetCount > 0;
        $hasPricingSection = (bool) $pricingCategory || !empty($product->pricing_page_url) || (float) ($product->price ?? 0) > 0;
        $hasResourcesSection = !empty($product->pricing_page_url) || !empty($product->x_account) || !empty($product->maker_links ?? []);
        $overviewBlocks = $descriptionContent['overview_blocks'] ?? [];
        $detailDescriptionHtml = $descriptionContent['details_html'] ?? null;
        $idealForItems = $bestForCategories->pluck('name')->take(2)->values();
        $quickFacts = collect([
            ['label' => 'Use Cases', 'value' => $useCaseCategories->isNotEmpty() ? $useCaseCategories->pluck('name')->take(2)->implode(', ') : null],
            ['label' => 'Pricing', 'value' => $pricingCategory?->name ?: ((float) ($product->price ?? 0) > 0 ? trim(($product->currency ?: 'USD') . ' ' . number_format((float) $product->price, 2)) : null)],
            ['label' => 'Platforms', 'value' => $platformCategories->isNotEmpty() ? $platformCategories->pluck('name')->take(2)->implode(', ') : null],
        ]);
        $quickFacts = $quickFacts->contains(fn($item) => filled($item['value']))
            ? $quickFacts->map(fn($item) => ['label' => $item['label'], 'value' => $item['value'] ?: 'Not listed yet'])->values()
            : collect();
        $sectionNavItems = array_values(array_filter([
            ['id' => 'overview', 'label' => 'Overview'],
            ($hasEditorialSections || filled($detailDescriptionHtml)) ? ['id' => 'details', 'label' => 'Details'] : null,
            $hasPricingSection ? ['id' => 'pricing', 'label' => 'Pricing'] : null,
            $alternativeProducts->isNotEmpty() ? ['id' => 'alternatives', 'label' => 'Alternatives'] : null,
            $hasResourcesSection ? ['id' => 'resources', 'label' => 'Resources'] : null,
            !empty($productEditorial['faq']) ? ['id' => 'faq', 'label' => 'FAQ'] : null,
        ]));
    @endphp
    <div id="product-detail-metrics" data-product-id="{{ $product->id }}" hidden></div>
    <div class="pt-2 pb-4">
        <x-breadcrumbs :items="$breadcrumbs" />
    </div>

    <div class="rounded-lg pt-2 pb-6 md:pt-3 md:pb-8"
        x-data="{
                                                                                        @if(isset($isAdminView) && $isAdminView)
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        editingName: false,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        editingTagline: false,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        editingProductPageTagline: false,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        editingDescription: false,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        editingCategories: false,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        editingLogo: false,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        editingVideoUrl: false,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        name: '{{ $product->name }}',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        tagline: '{{ $product->tagline }}',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        product_page_tagline: '{{ $product->product_page_tagline }}',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        description: `{{ $product->description }}`,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        category_ids: {{ $product->categories->pluck('id') }},
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        video_url: '{{ $product->video_url }}',
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        updateProduct() {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            let formData = new FormData();
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            formData.append('name', this.name);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            formData.append('tagline', this.tagline);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            formData.append('product_page_tagline', this.product_page_tagline);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            formData.append('description', this.description);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            formData.append('video_url', this.video_url);
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            this.category_ids.forEach(id => formData.append('categories[]', id));

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            let logoInput = document.querySelector('input[type="
                                                                                            file"]'); if (logoInput.files[0]) { formData.append('logo', logoInput.files[0]); } fetch('{{ route('admin.products.update', ['product' => $product->id]) }}', { method: 'POST' , // Use POST for FormData with
                                                                                            file uploads headers: { 'X-CSRF-TOKEN' : '{{ csrf_token() }}' , 'X-HTTP-Method-Override' : 'PUT' // Method spoofing
                                                                                            }, body: formData }).then(response=> {
                                                                                            if (response.ok) {
                                                                                            window.location.reload();
                                                                                            }
                                                                                            });
                                                                                            }
                                                                                        @endif
        }">
        <div class="overflow-visible rounded-[2rem] border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-5 sm:px-6 lg:px-8 xl:px-10">
                <div class="md:hidden">
                    @include('products.partials._header-mobile')
                </div>
                <div class="hidden md:block">
                    @include('products.partials._header-desktop')
                </div>

                @if($quickFacts->isNotEmpty())
                    <div class="mt-8 overflow-hidden rounded-2xl">
                        <dl class="grid divide-y divide-gray-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-3">
                            @foreach($quickFacts as $fact)
                                <div class="px-4 py-4 sm:px-5">
                                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $fact['label'] }}</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-800">{{ $fact['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div class="mt-8">
                    @include('products.partials._section-nav', ['sectionNavItems' => $sectionNavItems])
                </div>

                <section id="overview" class="scroll-mt-28 pt-8">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Overview</p>
                        @if(!empty($overviewBlocks))
                            <div class="prose product-detail-description ql-editor-content mt-4 max-w-none text-base text-gray-600">
                                @foreach($overviewBlocks as $overviewBlock)
                                    {!! \App\Support\OutboundLink::sanitizeHtml($overviewBlock, 'product_description') !!}
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                @if(isset($isAdminView) && $isAdminView)
                    <div class="mt-8 border-t border-gray-100 pt-8">
                        <h2 class="text-lg font-semibold mb-2">Video URL</h2>
                        <div x-show="!editingVideoUrl" @click="editingVideoUrl = true" x-text="video_url"></div>
                        <div x-show="editingVideoUrl">
                            <input type="text" x-model="video_url" class="form-input">
                            <button @click="updateProduct(); editingVideoUrl = false" class="btn btn-primary mt-2">Save</button>
                            <button @click="editingVideoUrl = false" class="btn btn-secondary mt-2">Cancel</button>
                        </div>
                    </div>
                @endif

                @if($hasMediaSection)
                    @php
                        $mediaSlides = [];

                        if ($product->video_url && $product->getEmbedUrl()) {
                            $mediaSlides[] = [
                                'type' => 'video',
                                'embed_url' => $product->getEmbedUrl(),
                                'title' => $product->name . ' video',
                            ];
                        }

                        foreach ($product->media as $mediaIndex => $media) {
                            $mediaSlides[] = [
                                'type' => 'image',
                                'src' => asset('storage/' . ($media->path_medium ?? $media->path)),
                                'srcset' => trim(implode(' ', array_filter([
                                    $media->path_thumb ? asset('storage/' . $media->path_thumb) . ' 300w,' : null,
                                    $media->path_medium ? asset('storage/' . $media->path_medium) . ' 800w,' : null,
                                    asset('storage/' . $media->path) . ' 1200w',
                                ]))),
                                'alt' => $media->alt_text ?: $product->name . ' screenshot ' . ($mediaIndex + 1),
                            ];
                        }

                        $hasMultipleMediaSlides = count($mediaSlides) > 1;
                    @endphp

                    <section class="mt-8">
                        @if($hasMultipleMediaSlides)
                            <div x-data="{
                                    canScrollLeft: false,
                                    canScrollRight: false,
                                    updateScroll() {
                                        const el = this.$refs.mediaContainer;
                                        if (!el) return;
                                        this.canScrollLeft = el.scrollLeft > 5;
                                        this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 5);
                                    },
                                    scroll(direction) {
                                        const el = this.$refs.mediaContainer;
                                        if (!el) return;
                                        el.scrollBy({ left: direction * el.clientWidth, behavior: 'smooth' });
                                    }
                                }" x-init="$nextTick(() => updateScroll())" class="relative group">
                                <button x-show="canScrollLeft" x-cloak @click="scroll(-1)"
                                    class="absolute left-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-gray-200 bg-white/95 p-2 shadow-sm transition hover:bg-white"
                                    aria-label="Scroll media left">
                                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                <button x-show="canScrollRight" x-cloak @click="scroll(1)"
                                    class="absolute right-3 top-1/2 z-10 -translate-y-1/2 rounded-full border border-gray-200 bg-white/95 p-2 shadow-sm transition hover:bg-white"
                                    aria-label="Scroll media right">
                                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>

                                <div x-ref="mediaContainer" @scroll.debounce.100ms="updateScroll()"
                                    class="flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth pb-2 no-scrollbar">
                                    @foreach($mediaSlides as $slide)
                                        <article class="w-full min-w-full snap-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                            @if($slide['type'] === 'video')
                                                <div class="aspect-video w-full">
                                                    <iframe src="{{ $slide['embed_url'] }}" class="h-full w-full"
                                                        title="{{ $slide['title'] }}" frameborder="0"
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                        allowfullscreen></iframe>
                                                </div>
                                            @else
                                                <img src="{{ $slide['src'] }}" srcset="{{ $slide['srcset'] }}"
                                                    sizes="100vw" alt="{{ $slide['alt'] }}"
                                                    class="block h-auto w-full object-contain"
                                                    itemprop="image"
                                                    loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @foreach($mediaSlides as $slide)
                                <article class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                    @if($slide['type'] === 'video')
                                        <div class="aspect-video w-full">
                                            <iframe src="{{ $slide['embed_url'] }}" class="h-full w-full"
                                                title="{{ $slide['title'] }}" frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen></iframe>
                                        </div>
                                    @else
                                        <img src="{{ $slide['src'] }}" srcset="{{ $slide['srcset'] }}"
                                            sizes="100vw" alt="{{ $slide['alt'] }}"
                                            class="block h-auto w-full object-contain"
                                            itemprop="image"
                                            loading="eager">
                                    @endif
                                </article>
                            @endforeach
                        @endif
                    </section>
                @endif

                <section id="details" class="scroll-mt-28 mt-4 pt-6" @class(['border-t border-gray-100' => !$hasMediaSection])>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Details</h2>
                    </div>

                    @if($hasEditorialSections)
                        <div class="mt-1">
                            @include('products.partials._editorial-highlights', ['productEditorial' => $productEditorial])
                        </div>

                        @if(filled($detailDescriptionHtml))
                            <details class="px-5 py-1">
                                <summary class="cursor-pointer text-sm font-semibold text-gray-900">Read full editorial notes</summary>
                                <div class="prose mt-2 max-w-none text-sm ql-editor-content product-detail-description">
                                    @if(isset($isAdminView) && $isAdminView)
                                        <div x-show="!editingDescription" @click="editingDescription = true">
                                            {!! \App\Support\OutboundLink::sanitizeHtml($detailDescriptionHtml, 'product_description') !!}
                                        </div>
                                        <textarea x-show="editingDescription" x-model="description"
                                            @keydown.enter="updateProduct(); editingDescription = false"
                                            @keydown.escape="editingDescription = false" class="form-input" rows="10"></textarea>
                                    @else
                                        {!! \App\Support\OutboundLink::sanitizeHtml($detailDescriptionHtml, 'product_description') !!}
                                    @endif
                                </div>
                            </details>
                        @endif
                    @else
                        @if(filled($detailDescriptionHtml))
                            <div class="px-5 py-1">
                                <div class="prose max-w-none text-sm ql-editor-content product-detail-description">
                                    @if(isset($isAdminView) && $isAdminView)
                                        <div x-show="!editingDescription" @click="editingDescription = true">
                                            {!! \App\Support\OutboundLink::sanitizeHtml($detailDescriptionHtml, 'product_description') !!}
                                        </div>
                                        <textarea x-show="editingDescription" x-model="description"
                                            @keydown.enter="updateProduct(); editingDescription = false"
                                            @keydown.escape="editingDescription = false" class="form-input" rows="10"></textarea>
                                    @else
                                        {!! \App\Support\OutboundLink::sanitizeHtml($detailDescriptionHtml, 'product_description') !!}
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </section>

                @if($hasPricingSection)
                    <section id="pricing" class="scroll-mt-28 mt-8 border-t border-gray-100 pt-8">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Pricing</h2>
                            <p class="mt-1 text-sm text-gray-500">The current pricing posture and the most useful pricing-related link we have for this product.</p>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-100 bg-gray-50">
                            <div class="grid divide-y divide-gray-200 md:grid-cols-3 md:divide-x md:divide-y-0">
                                <article class="px-5 py-5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Pricing Model</p>
                                    <p class="mt-2 text-base font-semibold text-gray-900">
                                        {{ $pricingCategory?->name ?: 'Not listed yet' }}
                                    </p>
                                </article>

                                <article class="px-5 py-5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Listed Price</p>
                                    <p class="mt-2 text-base font-semibold text-gray-900">
                                        @if((float) ($product->price ?? 0) > 0)
                                            {{ $product->currency ?: 'USD' }} {{ number_format((float) $product->price, 2) }}
                                        @else
                                            Not listed yet
                                        @endif
                                    </p>
                                </article>

                                <article class="px-5 py-5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Pricing Page</p>
                                    @if($product->pricing_page_url)
                                        <a href="{{ $product->pricing_page_url }}" target="_blank" rel="{{ \App\Support\OutboundLink::rel($product->pricing_page_url, 'pricing_page') }}"
                                            class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:underline">
                                            <span>View Pricing</span>
                                            <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @else
                                        <p class="mt-2 text-base font-semibold text-gray-900">Not listed yet</p>
                                    @endif
                                </article>
                            </div>
                        </div>
                    </section>
                @endif

                @if($alternativeProducts->isNotEmpty())
                    <section id="alternatives" class="scroll-mt-28 mt-8 border-t border-gray-100 pt-8">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Alternatives</h2>
                                <p class="mt-1 text-sm text-gray-500">A shortlist of related products to compare before you leave the page.</p>
                            </div>
                            <a href="{{ route('pseo.alternatives', $product->slug) }}" class="text-sm font-medium text-primary-600 hover:underline">
                                View all alternatives
                            </a>
                        </div>

                        <div class="mt-6">
                            @include('products.partials._alternatives-section', ['product' => $product, 'alternativeProducts' => $alternativeProducts])
                        </div>
                    </section>
                @endif

                @if($hasResourcesSection)
                    <section id="resources" class="scroll-mt-28 mt-8 border-t border-gray-100 pt-8">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Resources</h2>
                            <p class="mt-1 text-sm text-gray-500">Official profiles, useful links, and places to learn more about this product.</p>
                        </div>

                        <div class="mt-6">
                            @include('products.partials._resource-links', ['product' => $product])
                        </div>
                    </section>
                @endif

                @if(!empty($productEditorial['faq']))
                    <section id="faq" class="scroll-mt-28 mt-8 border-t border-gray-100 pt-8">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">FAQ</h2>
                            <p class="mt-1 text-sm text-gray-500">Common questions extracted from the editorial product description.</p>
                        </div>

                        <div class="mt-6 divide-y divide-gray-100">
                            @foreach($productEditorial['faq'] as $faqItem)
                                <article class="py-5 first:pt-0 last:pb-0">
                                    <h3 class="text-base font-semibold text-gray-900">{{ $faqItem['question'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-gray-600">{{ $faqItem['answer'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="md:hidden mt-8 border-t border-gray-100 pt-8">
                    @include('products.partials._sidebar-info')
                </div>

                @if(isset($isAdminView) && $isAdminView)
                    <div class="mt-8 border-t border-gray-100 pt-8">
                        <h2 class="text-lg font-semibold mb-2">Categories</h2>
                        <div x-show="!editingCategories" @click="editingCategories = true">
                            @foreach($product->categories as $category)
                                <span
                                    class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">{{ $category->name }}</span>
                            @endforeach
                        </div>
                        <div x-show="editingCategories">
                            <select multiple x-model="category_ids" class="form-multiselect block w-full mt-1">
                                @foreach($allCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <button @click="updateProduct(); editingCategories = false" class="btn btn-primary mt-2">Save</button>
                            <button @click="editingCategories = false" class="btn btn-secondary mt-2">Cancel</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('right_sidebar_content')
    <div class="hidden md:block space-y-6 md:pt-16">
        @include('partials._sidebar-ads')
        @include('products.partials._sidebar-info')
    </div>
@endsection

<template class="delayed-body-snippet">
    <script src="https://app.tinyadz.com/scripts/v1.0/ads.js" data-site-id="689a2b0d06e074933a271e16" async></script>
</template>
