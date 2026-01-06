@php $mainContentMaxWidth = 'max-w-full'; @endphp
@extends('layouts.app')

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('header-title')
    <div class="flex items-center">
        <a href="javascript:history.back()" class="text-gray-500 hover:text-gray-700 mr-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-lg md:text-base font-semibold tracking-tight">{{ $title }} details</h1>
    </div>
@endsection


@section('content')
    <div class="bg-white rounded-lg p-6 md:p-8 mt-4" x-data="{
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

                    let logoInput = document.querySelector('input[type=" file"]'); if (logoInput.files[0]) {
            formData.append('logo', logoInput.files[0]); } fetch('{{ route('admin.products.update', ['product' => $product->id]) }}', { method: 'POST' , // Use POST for FormData with file uploads headers: { 'X-CSRF-TOKEN' : '{{ csrf_token() }}'
            , 'X-HTTP-Method-Override' : 'PUT' // Method spoofing }, body: formData }).then(response=> {
            if (response.ok) {
            window.location.reload();
            }
            });
            }
        @endif
        }">
        <div class="gap-8">
            <div class="md:col-span-2">
                <div class="flex items-center mb-4">
                    @if(isset($isAdminView) && $isAdminView)
                        <div @click="editingLogo = true">
                            <template x-if="!editingLogo">
                                @if($product->logo)
                                    <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                                        alt="{{ $product->name }} logo" class="w-[65px] h-[65px] object-contain rounded-xl mr-3">
                                @elseif($product->link)
                                    <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}"
                                        alt="{{ $product->name }} favicon" class="size-14 object-contain rounded-xl mr-3">
                                @endif
                            </template>
                            <template x-if="editingLogo">
                                <div>
                                    <input type="file" class="form-input">
                                    <button @click.stop="updateProduct(); editingLogo = false"
                                        class="btn btn-primary mt-2">Save</button>
                                    <button @click.stop="editingLogo = false" class="btn btn-secondary mt-2">Cancel</button>
                                </div>
                            </template>
                        </div>
                    @else
                        @if($product->logo)
                            <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                                alt="{{ $product->name }} logo" class="w-[65px] h-[65px] object-contain rounded-xl mr-3">
                        @elseif($product->link)
                            <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}"
                                alt="{{ $product->name }} favicon" class="size-14 object-contain rounded-xl mr-3">
                        @endif
                    @endif
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            @if(isset($isAdminView) && $isAdminView)
                                <span x-show="!editingName" @click="editingName = true" x-text="name"></span>
                                <input x-show="editingName" x-model="name" @keydown.enter="updateProduct(); editingName = false"
                                    @keydown.escape="editingName = false" class="form-input">
                            @else
                                {{ $product->name }}
                            @endif
                        </h1>
                        <p class="text-gray-800 text-base">
                            <strong>Tagline:</strong>
                            @if(isset($isAdminView) && $isAdminView)
                                <span x-show="!editingTagline" @click="editingTagline = true" x-text="tagline"></span>
                                <input x-show="editingTagline" x-model="tagline"
                                    @keydown.enter="updateProduct(); editingTagline = false"
                                    @keydown.escape="editingTagline = false" class="form-input">
                            @else
                                {{ $product->tagline }}
                            @endif
                        </p>
                        <p class="text-gray-800 text-base">
                            <strong>Product Page Tagline:</strong>
                            @if(isset($isAdminView) && $isAdminView)
                                <span x-show="!editingProductPageTagline" @click="editingProductPageTagline = true"
                                    x-text="product_page_tagline"></span>
                                <input x-show="editingProductPageTagline" x-model="product_page_tagline"
                                    @keydown.enter="updateProduct(); editingProductPageTagline = false"
                                    @keydown.escape="editingProductPageTagline = false" class="form-input">
                            @else
                                {{ $product->product_page_tagline }}
                            @endif
                        </p>
                        <div class="flex flex-wrap items-center mt-1">
                            @php
                                $generalCategories = $product->categories->filter(function ($cat) {
                                    return !$cat->types->contains('name', 'Pricing') && !$cat->types->contains('name', 'Best for');
                                });
                            @endphp
                            @foreach($generalCategories as $category)
                                <a href="{{ route('categories.show', ['category' => $category->slug]) }}"
                                    class="text-[0.65rem] text-gray-500 hover:underline">{{ $category->name }}</a>
                                @if(!$loop->last)
                                    <span class="text-gray-400 mx-2">&middot;</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        @unless($product->user->hasRole('admin'))
                            <div class="flex flex-col gap-1">
                                <div class="text-xs font-medium">
                                    Publisher
                                </div>
                                <div class="flex flex-row">
                                    <div>
                                        <img src="{{ $product->user->avatar() }}" alt="{{ $product->user->name }}"
                                            class="size-5 rounded-full mr-1 border">
                                    </div>
                                    <div class="text-gray-700 text-xs content-center">
                                        {{ $product->user->name }}
                                    </div>

                                </div>
                            </div>


                        @endunless
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="{{ $product->link . (strpos($product->link, '?') === false ? '?' : '&') }}utm_source=softwareontheweb.com"
                            target="_blank" rel="noopener ugc noreferrer"
                            class="inline-flex items-center px-4 py-1.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Visit Website &nbsp;
                            <svg class="size-4 stroke-gray-600" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <path d="M7 17L17 7M17 7H8M17 7V16" stroke="" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round"></path>
                                </g>
                            </svg>
                        </a>
                        @unless(isset($isAdminView) && $isAdminView)
                            @livewire('product-upvote-button', ['product' => $product])
                        @endunless
                    </div>
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
                    <div x-data="{ open: false, mediaUrl: '', isVideo: false }">
                        <div class="flex flex-wrap gap-4 mb-6">
                            @if($product->video_url)
                                @php
                                    $embedUrl = $product->getEmbedUrl();
                                    $thumbnailUrl = 'https://img.youtube.com/vi/' . $product->getVideoId() . '/0.jpg';
                                @endphp
                                <div class="cursor-pointer" @click="open = true; mediaUrl = '{{ $embedUrl }}'; isVideo = true">
                                    <div class="relative w-[392px] h-[221px] rounded-lg overflow-hidden border">
                                        <img src="{{ $thumbnailUrl }}" alt="Video Thumbnail" class="w-full h-full object-cover"
                                            loading="lazy">
                                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                            <svg class="w-16 h-16 text-white opacity-75 hover:opacity-100 transition-opacity"
                                                viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @foreach($product->media as $media)
                                <div class="cursor-pointer"
                                    @click="open = true; mediaUrl = '{{ asset('storage/' . $media->path) }}'; isVideo = false">
                                    <img src="{{ asset('storage/' . $media->path) }}" alt="{{ $media->alt_text }}"
                                        class="w-[392px] h-[221px] object-cover rounded-lg border" loading="lazy">
                                </div>
                            @endforeach
                        </div>

                        <!-- Modal -->
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
                            <button @click="updateProduct(); editingCategories = false"
                                class="btn btn-primary mt-2">Save</button>
                            <button @click="editingCategories = false" class="btn btn-secondary mt-2">Cancel</button>
                        </div>
                    </div>
                @endif
            </div>
            <div class="hidden md:block md:col-span-1">
                @section('right_sidebar_content')
                    @include('products.partials._sidebar-info')
                @endsection
            </div>
        </div>
    </div>
@endsection
<script src="https://app.tinyadz.com/scripts/v1.0/ads.js" data-site-id="689a2b0d06e074933a271e16" async></script>