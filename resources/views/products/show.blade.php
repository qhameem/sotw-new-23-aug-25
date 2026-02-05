@php 
                                $mainContentMaxWidth = 'max-w-7xl';
    $title = '';
    $mainPadding = 'px-6 sm:px-6 lg:px-8';
    $headerPadding = 'px-6 sm:px-6 lg:px-8';
@endphp
@extends('layouts.app')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)




@section('content')
    @include('products.partials._json-ld-product')
    <div class="py-4">
        <x-breadcrumbs :items="[['label' => $product->name]]" />
    </div>

    <div class="bg-white rounded-lg py-6 md:py-8"
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
                {{-- Media gallery carousel logic remains here --}}
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
                    class="relative group mt-4">

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
                        class="flex overflow-x-auto scroll-smooth no-scrollbar gap-4 mb-6 pb-2">

                        @if($product->video_url)
                            @php
                                $embedUrl = $product->getEmbedUrl();
                                $thumbnailUrl = 'https://img.youtube.com/vi/' . $product->getVideoId() . '/hqdefault.jpg';
                            @endphp
                            <div class="flex-shrink-0 cursor-pointer"
                                @click="open = true; mediaUrl = '{{ $embedUrl }}'; isVideo = true">
                                <div
                                    class="relative w-[240px] sm:w-[260px] md:w-[280px] aspect-video rounded-xl overflow-hidden border shadow-sm hover:shadow-md transition-shadow">
                                    <img src="{{ $thumbnailUrl }}" alt="Video Thumbnail" class="w-full h-full object-cover"
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
                            <div class="flex-shrink-0 cursor-pointer"
                                @click="open = true; mediaUrl = '{{ asset('storage/' . $media->path) }}'; isVideo = false">
                                <div
                                    class="relative w-[240px] sm:w-[260px] md:w-[280px] aspect-video rounded-xl overflow-hidden border shadow-sm hover:shadow-md transition-shadow">
                                    <img src="{{ asset('storage/' . ($media->path_medium ?? $media->path)) }}" srcset="{{ $media->path_thumb ? asset('storage/' . $media->path_thumb) . ' 300w,' : '' }}
                                                            {{ $media->path_medium ? asset('storage/' . $media->path_medium) . ' 800w,' : '' }}
                                                            {{ asset('storage/' . $media->path) }} 1200w"
                                        alt="{{ $media->alt_text }}" class="w-full h-full object-cover"
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
            @endif

            <div class="prose max-w-none text-sm ql-editor-content">
                @if(isset($isAdminView) && $isAdminView)
                    <div x-show="!editingDescription" @click="editingDescription = true" x-html="description"></div>
                    <textarea x-show="editingDescription" x-model="description"
                        @keydown.enter="updateProduct(); editingDescription = false"
                        @keydown.escape="editingDescription = false" class="form-input" rows="10"></textarea>
                @else
                    {!! $product->description !!}
                @endif
            </div>

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

        @section('right_sidebar_content')
            <div class="hidden md:block">
                @include('products.partials._sidebar-info')
            </div>
        @endsection
    </div>
@endsection
<script src="https://app.tinyadz.com/scripts/v1.0/ads.js" data-site-id="689a2b0d06e074933a271e16" async></script>