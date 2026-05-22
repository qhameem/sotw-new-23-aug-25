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
        $hasPricingSection = (bool) $pricingCategory || !empty($product->pricing_page_url) || (float) ($product->price ?? 0) > 0;
        $hasResourcesSection = !empty($product->pricing_page_url) || !empty($product->x_account) || !empty($product->maker_links ?? []);
        $overviewParagraphs = array_values(array_filter([
            $productEditorial['headline'] ?? null,
            $productEditorial['summary'] ?? null,
        ]));
        $fallbackOverview = \Illuminate\Support\Str::limit(
            trim(strip_tags((string) ($product->description ?: $product->product_page_tagline ?: $product->tagline))),
            260
        );
        $quickFacts = collect([
            ['label' => 'Pricing', 'value' => $pricingCategory?->name ?: ((float) ($product->price ?? 0) > 0 ? trim(($product->currency ?: 'USD') . ' ' . number_format((float) $product->price, 2)) : null)],
            ['label' => 'Use Cases', 'value' => $useCaseCategories->isNotEmpty() ? $useCaseCategories->pluck('name')->take(2)->implode(', ') : null],
            ['label' => 'Ideal For', 'value' => $bestForCategories->isNotEmpty() ? $bestForCategories->pluck('name')->take(2)->implode(', ') : null],
            ['label' => 'Platforms', 'value' => $platformCategories->isNotEmpty() ? $platformCategories->pluck('name')->take(2)->implode(', ') : null],
        ])->filter(fn($item) => filled($item['value']))->values();
        $sectionNavItems = array_values(array_filter([
            ['id' => 'overview', 'label' => 'Overview'],
            $mediaAssetCount > 0 ? ['id' => 'media', 'label' => 'Media'] : null,
            ($hasEditorialSections || filled($product->description)) ? ['id' => 'details', 'label' => 'Details'] : null,
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
        <div class="p-0">
            <div class="md:hidden">
                @include('products.partials._header-mobile')
            </div>
            <div class="hidden md:block">
                @include('products.partials._header-desktop')
            </div>

            @if($quickFacts->isNotEmpty())
                <div class="mb-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($quickFacts as $fact)
                        <article class="rounded-2xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ $fact['label'] }}</p>
                            <p class="mt-1 text-sm font-medium text-gray-800">{{ $fact['value'] }}</p>
                        </article>
                    @endforeach
                </div>
            @endif

            @include('products.partials._section-nav', ['sectionNavItems' => $sectionNavItems])

            <section id="overview" class="scroll-mt-28 mb-10">
                <div class="rounded-3xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 p-6 shadow-sm">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Overview</p>
                        <h2 class="mt-2 text-2xl font-semibold text-gray-900">{{ $product->product_page_tagline ?: $product->tagline }}</h2>

                        @if(!empty($overviewParagraphs))
                            <div class="mt-4 space-y-4 text-base leading-7 text-gray-600">
                                @foreach($overviewParagraphs as $paragraph)
                                    <p>{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        @elseif($fallbackOverview !== '')
                            <p class="mt-4 text-base leading-7 text-gray-600">{{ $fallbackOverview }}</p>
                        @endif
                    </div>
                </div>
            </section>

            @if(isset($isAdminView) && $isAdminView)
                <div class="mt-6">
                    <h2 class="text-lg font-semibold mb-2">Video URL</h2>
                    <div x-show="!editingVideoUrl" @click="editingVideoUrl = true" x-text="video_url"></div>
                    <div x-show="editingVideoUrl">
                        <input type="text" x-model="video_url" class="form-input">
                        <button @click="updateProduct(); editingVideoUrl = false" class="btn btn-primary mt-2">Save</button>
                        <button @click="editingVideoUrl = false" class="btn btn-secondary mt-2">Cancel</button>
                    </div>
                </div>
            @endif

            @if($product->video_url || $product->media->isNotEmpty())
                <section id="media" class="scroll-mt-28 mb-10">
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Media</h2>
                            <p class="mt-1 text-sm text-gray-500">Screenshots and video previews for a quick product tour.</p>
                        </div>
                    </div>

                    <div x-data="{
                                                                            open: false,
                                                                            mediaUrl: '',
                                                                            isVideo: false,
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
                                                                                const scrollAmount = el.clientWidth * 0.8;
                                                                                el.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
                                                                            }
                                                                        }" x-init="$nextTick(() => updateScroll())"
                        class="relative group rounded-3xl border border-gray-200 bg-white p-5 shadow-sm">

                        <!-- Left Navigation Arrow -->
                        <button x-show="canScrollLeft" x-cloak @click="scroll(-1)"
                            class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 bg-white shadow-lg border border-gray-200 rounded-full p-2 hover:bg-gray-50 transition-all opacity-0 group-hover:opacity-100 focus:opacity-100"
                            aria-label="Scroll Left">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <!-- Right Navigation Arrow -->
                        <button x-show="canScrollRight" x-cloak @click="scroll(1)"
                            class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 bg-white shadow-lg border border-gray-200 rounded-full p-2 hover:bg-gray-50 transition-all opacity-0 group-hover:opacity-100 focus:opacity-100"
                            aria-label="Scroll Right">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>

                        <div x-ref="mediaContainer" @scroll.debounce.100ms="updateScroll()"
                            class="flex items-start overflow-x-auto scroll-smooth no-scrollbar gap-4 pb-2">

                            @if($product->video_url)
                                @php
                                    $embedUrl = $product->getEmbedUrl();
                                    $thumbnailUrl = 'https://img.youtube.com/vi/' . $product->getVideoId() . '/hqdefault.jpg';
                                @endphp
                                <div class="flex-shrink-0 self-start cursor-pointer"
                                    @click="open = true; mediaUrl = '{{ $embedUrl }}'; isVideo = true">
                                    <div
                                        class="relative w-[240px] sm:w-[260px] md:w-[280px] aspect-w-16 aspect-h-9 rounded-xl overflow-hidden border shadow-sm hover:shadow-md transition-shadow">
                                        <img src="{{ $thumbnailUrl }}" alt="Video Thumbnail" class="block w-full h-full object-cover"
                                            itemprop="image"
                                            loading="eager" decoding="async"
                                            sizes="(min-width: 768px) 280px, (min-width: 640px) 260px, 240px">
                                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center group/play">
                                            <div
                                                class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center transition-transform group-hover/play:scale-110">
                                                <svg class="w-8 h-8 text-white fill-current" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif


                            @foreach($product->media as $media)
                                <div class="flex-shrink-0 self-start cursor-pointer"
                                    @click="open = true; mediaUrl = '{{ asset('storage/' . $media->path) }}'; isVideo = false">
                                    <div
                                        class="relative w-[240px] sm:w-[260px] md:w-[280px] aspect-w-16 aspect-h-9 rounded-xl overflow-hidden border shadow-sm hover:shadow-md transition-shadow">
                                        <img src="{{ asset('storage/' . ($media->path_medium ?? $media->path)) }}" srcset="{{ $media->path_thumb ? asset('storage/' . $media->path_thumb) . ' 300w,' : '' }}
                                                                        {{ $media->path_medium ? asset('storage/' . $media->path_medium) . ' 800w,' : '' }}
                                                                        {{ asset('storage/' . $media->path) }} 1200w"
                                            alt="{{ $media->alt_text ?: $product->name . ' screenshot ' . $loop->iteration }}" class="block w-full h-full object-cover"
                                            itemprop="image"
                                            loading="{{ ($loop->first && !$product->video_url) ? 'eager' : 'lazy' }}"
                                            sizes="(min-width: 768px) 280px, (min-width: 640px) 260px, 240px">
                                    </div>
                                </div>
                            @endforeach

                        </div>

                        <!-- Modal -->
                        <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4"
                            style="display: none;">
                            <div @click.away="open = false" class="relative max-w-4xl w-full">
                                <!-- Modal content -->
                                <div class="relative bg-white rounded-lg max-h-[90vh] overflow-y-auto">
                                    <!-- Media content -->
                                    <div class="p-0">
                                        <template x-if="isVideo">
                                            <div class="aspect-w-16 aspect-h-9">
                                                <iframe :src="mediaUrl" class="w-full h-full" frameborder="0"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen></iframe>
                                            </div>
                                        </template>
                                        <template x-if="!isVideo">
                                            <img :src="mediaUrl" alt="Full size media" class="w-full h-auto rounded-xl">
                                        </template>
                                    </div>
                                </div>
                                <!-- Close button -->
                                <button @click="open = false"
                                    class="absolute -top-2 -right-2 text-white bg-gray-800 rounded-full p-1 z-20 hover:bg-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section id="details" class="scroll-mt-28 mb-10">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Details</h2>
                    <p class="mt-1 text-sm text-gray-500">A more scannable breakdown of features, audience fit, and product tradeoffs.</p>
                </div>

                @if($hasEditorialSections)
                    @include('products.partials._editorial-highlights', ['productEditorial' => $productEditorial])

                    @if(filled($product->description))
                        <details class="mt-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <summary class="cursor-pointer text-sm font-semibold text-gray-900">Read full editorial notes</summary>
                            <div class="prose mt-4 max-w-none text-base ql-editor-content product-detail-description">
                                @if(isset($isAdminView) && $isAdminView)
                                    <div x-show="!editingDescription" @click="editingDescription = true" x-html="description"></div>
                                    <textarea x-show="editingDescription" x-model="description"
                                        @keydown.enter="updateProduct(); editingDescription = false"
                                        @keydown.escape="editingDescription = false" class="form-input" rows="10"></textarea>
                                @else
                                    {!! \App\Support\OutboundLink::sanitizeHtml($product->description, 'product_description') !!}
                                @endif
                            </div>
                        </details>
                    @endif
                @else
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="prose max-w-none text-base ql-editor-content product-detail-description">
                            @if(isset($isAdminView) && $isAdminView)
                                <div x-show="!editingDescription" @click="editingDescription = true" x-html="description"></div>
                                <textarea x-show="editingDescription" x-model="description"
                                    @keydown.enter="updateProduct(); editingDescription = false"
                                    @keydown.escape="editingDescription = false" class="form-input" rows="10"></textarea>
                            @else
                                {!! \App\Support\OutboundLink::sanitizeHtml($product->description, 'product_description') !!}
                            @endif
                        </div>
                    </div>
                @endif
            </section>

            @if($hasPricingSection)
                <section id="pricing" class="scroll-mt-28 mb-10">
                    <div class="mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Pricing</h2>
                        <p class="mt-1 text-sm text-gray-500">The current pricing posture and the most useful pricing-related link we have for this product.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Pricing Model</p>
                            <p class="mt-2 text-base font-semibold text-gray-900">
                                {{ $pricingCategory?->name ?: 'Not listed yet' }}
                            </p>
                        </article>

                        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Listed Price</p>
                            <p class="mt-2 text-base font-semibold text-gray-900">
                                @if((float) ($product->price ?? 0) > 0)
                                    {{ $product->currency ?: 'USD' }} {{ number_format((float) $product->price, 2) }}
                                @else
                                    Not listed yet
                                @endif
                            </p>
                        </article>

                        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
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
                </section>
            @endif

            @if($alternativeProducts->isNotEmpty())
                <section id="alternatives" class="scroll-mt-28 mb-10">
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Alternatives</h2>
                            <p class="mt-1 text-sm text-gray-500">A shortlist of related products to compare before you leave the page.</p>
                        </div>
                        <a href="{{ route('pseo.alternatives', $product->slug) }}" class="text-sm font-medium text-primary-600 hover:underline">
                            View all alternatives
                        </a>
                    </div>

                    @include('products.partials._alternatives-section', ['product' => $product, 'alternativeProducts' => $alternativeProducts])
                </section>
            @endif

            @if($hasResourcesSection)
                <section id="resources" class="scroll-mt-28 mb-10">
                    <div class="mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Resources</h2>
                        <p class="mt-1 text-sm text-gray-500">Official profiles, useful links, and places to learn more about this product.</p>
                    </div>

                    @include('products.partials._resource-links', ['product' => $product])
                </section>
            @endif

            @if(!empty($productEditorial['faq']))
                <section id="faq" class="scroll-mt-28 mb-10">
                    <div class="mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">FAQ</h2>
                        <p class="mt-1 text-sm text-gray-500">Common questions extracted from the editorial product description.</p>
                    </div>

                    <div class="space-y-3">
                        @foreach($productEditorial['faq'] as $faqItem)
                            <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                                <h3 class="text-base font-semibold text-gray-900">{{ $faqItem['question'] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-gray-600">{{ $faqItem['answer'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <div class="md:hidden mt-8">
                @include('products.partials._sidebar-info')
            </div>

            @if(isset($isAdminView) && $isAdminView)
                <div class="mt-6">
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
@endsection

@section('right_sidebar_content')
    <div class="hidden md:block space-y-6">
        @unless(isset($isAdminView) && $isAdminView)
            <div class="mt-[5.25rem] rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                @include('partials.product-upvote-button', ['product' => $product, 'compact' => true])
            </div>
        @endunless
        @include('partials._sidebar-ads')
        @include('products.partials._sidebar-info')
    </div>
@endsection

<template class="delayed-body-snippet">
    <script src="https://app.tinyadz.com/scripts/v1.0/ads.js" data-site-id="689a2b0d06e074933a271e16" async></script>
</template>
