@extends('layouts.app')

@section('title', 'My Products | Software on the Web')

@section('header-title')
<h2 class="text-base font-semibold py-[3px] hidden md:block">My Products</h2>
@endsection

@section('content')
    <div class="p-4">
        <div class="flex justify-end mb-4">
            <div class="flex items-center space-x-2">
                <label for="per_page" class="text-sm text-gray-600">Show:</label>
                <select id="per_page" name="per_page" onchange="window.location.href = this.value;" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    @foreach($allowedPerPages as $option)
                        <option value="{{ route('products.my', ['per_page' => $option]) }}" {{ $perPage == $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        @if($products->isEmpty())
            <div class="text-center text-gray-500 py-12">
                <p class="text-xl mb-2">You haven't submitted any products yet.</p>
                <a href="{{ route('products.create') }}" class="text-blue-500 hover:underline">Submit your first product!</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($products as $product)
                    @php
                        $logo = $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null;
                        $favicon = 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link);
                    @endphp
                    <article x-data="productEditor({ 
                        product: {{ json_encode(array_merge($product->only(['id', 'name', 'tagline', 'product_page_tagline', 'description', 'link', 'x_account', 'sell_product', 'asking_price', 'maker_links', 'video_url']), ['logo_url' => $product->logo_url])) }},
                        categories: {{ $product->categories->pluck('id')->toJson() }},
                        tech_stacks: {{ $product->techStacks->pluck('id')->toJson() }},
                        media: {{ $product->media->map->only(['id', 'path', 'type', 'alt_text'])->toJson() }}
                    })" @description-updated.window="if($event.detail.id === product.id) tempValue = $event.detail.content" class="bg-white p-4 md:p-4 border rounded-lg flex flex-col md:flex-row gap-4 md:gap-6 relative group" itemscope itemtype="https://schema.org/Product">
                        
                        <!-- Overlay Loading -->
                        <div x-show="loading" class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center rounded-lg">
                            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <div class="flex-1">
                            <div class="flex items-stretch gap-4 flex-col md:flex-row mb-4">
                                <!-- Logo Card -->
                                <div class="flex flex-col items-center justify-center border border-gray-100 rounded-lg p-3 bg-white shadow-sm min-w-[120px]">
                                    <div class="relative group mb-2">
                                        <img :src="product.logo_url" alt="" class="w-16 h-16 md:w-20 md:h-20 rounded-xl object-cover border border-gray-50" itemprop="image">
                                    </div>
                                    <label class="text-blue-600 hover:text-blue-800 text-[0.65rem] font-bold tracking-wider cursor-pointer text-center">
                                        Replace
                                        <input type="file" class="hidden" @change="uploadLogo">
                                    </label>
                                </div>
                                
                                <!-- Name Card -->
                                <div class="flex-1 flex items-center justify-between border border-gray-100 rounded-lg p-4 bg-gray-50/30 shadow-sm relative">
                                    <div class="flex flex-col">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider mb-1">Product Name</p>
                                        <div class="flex items-center gap-2">
                                            <h2 x-show="editingField !== 'name'" class="text-lg md:text-xl font-bold leading-tight" itemprop="name">
                                                <a :href="product.link" target="_blank" rel="noopener nofollow" class="hover:underline" itemprop="url" x-text="product.name"></a>
                                            </h2>
                                        </div>
                                    </div>
                                    <button x-show="editingField !== 'name'" @click="startEdit('name', product.name)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                        Edit
                                    </button>
                                    
                                    <div x-show="editingField === 'name'" class="flex items-center gap-2 w-full">
                                        <input type="text" x-model="tempValue" class="text-base md:text-lg font-bold border-gray-300 rounded px-2 py-1 w-full focus:ring-blue-500 focus:border-blue-500">
                                        <div class="flex gap-1">
                                            <button @click="save('name')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Save</button>
                                            <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col gap-4 w-full">
                                <!-- Tagline -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Tagline</p>
                                        <button x-show="editingField !== 'tagline'" @click="startEdit('tagline', product.tagline)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <div x-show="editingField !== 'tagline'">
                                        <p class="text-gray-700 text-sm" itemprop="tagline" x-text="product.tagline"></p>
                                    </div>
                                    <div x-show="editingField === 'tagline'" class="flex items-center gap-2 mt-1">
                                        <input type="text" x-model="tempValue" class="text-sm border-gray-300 rounded px-2 py-1 w-full">
                                        <button @click="save('tagline')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                        <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded">Cancel</button>
                                    </div>
                                </div>
                                
                                <!-- Product Page Tagline -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Product Page Tagline</p>
                                        <button x-show="editingField !== 'product_page_tagline'" @click="startEdit('product_page_tagline', product.product_page_tagline)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <div x-show="editingField !== 'product_page_tagline'">
                                        <p class="text-gray-700 text-sm" x-text="product.product_page_tagline || 'Not set'"></p>
                                    </div>
                                    <div x-show="editingField === 'product_page_tagline'" class="flex items-center gap-2 mt-1">
                                        <input type="text" x-model="tempValue" class="text-sm border-gray-300 rounded px-2 py-1 w-full" placeholder="Enter product page tagline">
                                        <button @click="save('product_page_tagline')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                        <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded">Cancel</button>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="md:col-span-2 border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Description</p>
                                        <button x-show="editingField !== 'description'" @click="startEdit('description', product.description)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <div x-show="editingField !== 'description'" class="prose prose-sm text-sm max-w-none text-gray-600" itemprop="description" x-html="product.description"></div>
                                    <div x-show="editingField === 'description'" class="mt-1"
                                        x-init="$watch('editingField', value => { 
                                            if (value === 'description') {
                                                $nextTick(() => {
                                                    const editorId = 'editor-' + product.id;
                                                    if (window.Quill) {
                                                        const quill = new Quill('#' + editorId, {
                                                            theme: 'snow',
                                                            modules: { 
                                                                toolbar: [
                                                                    [{ 'header': [2, 3, false] }],
                                                                    ['bold', 'italic', 'underline'],
                                                                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                                                    ['link', 'clean']
                                                                ] 
                                                            }
                                                        });
                                                        quill.root.innerHTML = tempValue;
                                                        quill.on('text-change', () => {
                                                            $dispatch('description-updated', { id: product.id, content: quill.root.innerHTML });
                                                        });
                                                    }
                                                });
                                            }
                                        })">
                                        <div :id="'editor-' + product.id" class="bg-white" style="height: 200px;"></div>
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button @click="cancelEdit()" class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded hover:bg-gray-200">Cancel</button>
                                            <button @click="save('description')" class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Save Changes</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Video URL -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Video URL</p>
                                        <button x-show="editingField !== 'video_url'" @click="startEdit('video_url', product.video_url)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <p x-show="editingField !== 'video_url'" class="text-sm text-blue-600 break-all">
                                        <template x-if="product.video_url">
                                            <a :href="product.video_url" target="_blank" rel="noopener nofollow" x-text="product.video_url"></a>
                                        </template>
                                        <template x-if="!product.video_url">
                                            <span class="text-gray-400 italic">None</span>
                                        </template>
                                    </p>
                                    <div x-show="editingField === 'video_url'" class="flex items-center gap-2 mt-1">
                                        <input type="url" x-model="tempValue" class="text-sm border-gray-300 rounded px-2 py-1 w-full" placeholder="https://youtube.com/...">
                                        <button @click="save('video_url')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                        <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded">Cancel</button>
                                    </div>
                                </div>
                                
                                <!-- Link -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Link</p>
                                        <button x-show="editingField !== 'link'" @click="startEdit('link', product.link)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <p x-show="editingField !== 'link'" class="text-sm text-blue-600 break-all">
                                        <a :href="product.link" target="_blank" rel="noopener nofollow" x-text="product.link"></a>
                                    </p>
                                    <div x-show="editingField === 'link'" class="flex items-center gap-2 mt-1">
                                        <input type="url" x-model="tempValue" class="text-sm border-gray-300 rounded px-2 py-1 w-full">
                                        <button @click="save('link')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                        <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded">Cancel</button>
                                    </div>
                                </div>
                                
                                <!-- Maker Links -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Maker Links</p>
                                        <button x-show="editingField !== 'maker_links'" @click="startEdit('maker_links', product.maker_links || [])" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <div x-show="editingField !== 'maker_links'" class="flex flex-wrap gap-2">
                                        <template x-if="!product.maker_links || product.maker_links.length === 0">
                                            <p class="text-sm text-gray-400 italic">None</p>
                                        </template>
                                        <template x-for="link in product.maker_links" :key="link">
                                            <a :href="link" target="_blank" rel="noopener nofollow" class="text-sm text-blue-600 hover:underline break-all" x-text="link"></a>
                                        </template>
                                    </div>
                                    <div x-show="editingField === 'maker_links'" class="mt-1 space-y-2">
                                        <template x-for="(link, index) in tempValue" :key="index">
                                            <div class="flex items-center gap-2">
                                                <input type="url" x-model="tempValue[index]" class="text-sm border-gray-300 rounded px-2 py-1 w-full" placeholder="https://...">
                                                <button @click="tempValue.splice(index, 1)" class="text-red-500 hover:bg-red-50 p-1 rounded"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                            </div>
                                        </template>
                                        <div class="flex justify-between items-center">
                                            <button @click="tempValue.push('')" class="text-xs font-medium text-blue-600 hover:underline">+ Add link</button>
                                            <div class="flex gap-2">
                                                <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded">Cancel</button>
                                                <button @click="save('maker_links')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- X Account -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">X Account</p>
                                        <button x-show="editingField !== 'x_account'" @click="startEdit('x_account', product.x_account)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <p x-show="editingField !== 'x_account'" class="text-sm text-gray-700">
                                        <a x-show="product.x_account" :href="'https://x.com/' + product.x_account" target="_blank" rel="noopener nofollow" class="text-blue-600 hover:underline">@<span x-text="product.x_account"></span></a>
                                        <span x-show="!product.x_account" class="text-gray-400 italic">None</span>
                                    </p>
                                    <div x-show="editingField === 'x_account'" class="flex items-center gap-2 mt-1">
                                        <div class="relative w-full">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm">@</span>
                                            <input type="text" x-model="tempValue" class="text-sm border-gray-300 rounded pl-6 pr-2 py-1 w-full" placeholder="username">
                                        </div>
                                        <button @click="save('x_account')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                        <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-500 bg-gray-100 rounded">Cancel</button>
                                    </div>
                                </div>
                                
                                <!-- Selling Info -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Selling Info</p>
                                        <button x-show="editingField !== 'selling'" @click="editingField = 'selling'; tempValue = {sell_product: product.sell_product, asking_price: product.asking_price}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <div x-show="editingField !== 'selling'">
                                        <p class="text-sm text-gray-700">
                                            <span x-text="product.sell_product ? 'For Sale' : 'Not for sale'"></span>
                                            <template x-if="product.sell_product && product.asking_price">
                                                <span x-text="' - Asking Price: $' + parseFloat(product.asking_price).toLocaleString()"></span>
                                            </template>
                                        </p>
                                    </div>
                                    <div x-show="editingField === 'selling'" class="mt-1 space-y-2">
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" x-model="tempValue.sell_product" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">Allow for sale</span>
                                        </label>
                                        <div x-show="tempValue.sell_product" class="flex items-center gap-2">
                                            <span class="text-sm text-gray-500">$</span>
                                            <input type="number" x-model="tempValue.asking_price" step="0.01" class="text-sm border-gray-300 rounded px-2 py-1 w-full" placeholder="Asking price">
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded">Cancel</button>
                                            <button @click="saveSelling()" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tech Stacks -->
                                <div class="border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Tech Stacks</p>
                                        <button x-show="editingField !== 'tech_stacks'" @click="startEdit('tech_stacks', techStacks)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    <div x-show="editingField !== 'tech_stacks'" class="flex flex-wrap gap-2 mt-1">
                                        <template x-if="techStacks.length === 0">
                                            <p class="text-sm text-gray-400 italic">None</p>
                                        </template>
                                        <template x-for="techId in techStacks" :key="techId">
                                            <span class="inline-block px-2 py-1 text-xs bg-gray-100 rounded" x-text="getTechName(techId)"></span>
                                        </template>
                                    </div>
                                    <div x-show="editingField === 'tech_stacks'" class="mt-1">
                                        <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto p-2 border rounded bg-gray-50">
                                            @foreach($allTechStacks as $tech)
                                                <label class="flex items-center gap-1 bg-white px-2 py-1 rounded border cursor-pointer hover:bg-blue-50 transition-colors">
                                                    <input type="checkbox" :value="{{ $tech->id }}" x-model="tempValue" class="rounded border-gray-300 text-blue-600">
                                                    <span class="text-xs">{{ $tech->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button @click="cancelEdit()" class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded">Cancel</button>
                                            <button @click="save('tech_stacks')" class="px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded">Save</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Categories Section -->
                                <div class="md:col-span-2 mt-2 border border-blue-50 bg-blue-50/10 rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-[0.7rem] font-bold text-blue-800 uppercase tracking-wider">Manage Categories</p>
                                        <button x-show="editingField !== 'categories'" @click="startEdit('categories', categories)" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                            Edit
                                        </button>
                                    </div>
                                    
                                    <div x-show="editingField !== 'categories'" class="space-y-3 mt-2">
                                        <!-- Category Grouping Logic within JS/Alpine -->
                                        <div>
                                            <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Category</p>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                <template x-for="catId in getSoftwareCats()" :key="catId">
                                                    <span class="text-gray-600 text-[0.65rem] border px-1.5 py-0.5 rounded" x-text="getCategoryName(catId)"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Pricing</p>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                <template x-for="catId in getPricingCats()" :key="catId">
                                                    <span class="text-gray-600 text-[0.65rem] border px-1.5 py-0.5 rounded" x-text="getCategoryName(catId)"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider">Best For</p>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                <template x-for="catId in getBestForCats()" :key="catId">
                                                    <span class="text-gray-600 text-[0.65rem] border px-1.5 py-0.5 rounded" x-text="getCategoryName(catId)"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <div x-show="editingField === 'categories'" class="mt-2 space-y-4 bg-gray-50 p-3 rounded border">
                                        @foreach(['Software Categories' => 'Software', 'Pricing' => 'Pricing', 'Best for' => 'Best for'] as $title => $typeName)
                                            <div>
                                                <p class="text-[0.7rem] font-bold text-gray-500 mb-1">{{ $title }}</p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($allCategories as $cat)
                                                        @if(($typeName === 'Software' && ($cat->types->contains('name', 'Software') || $cat->types->contains('name', 'Category') || $cat->types->isEmpty())) || ($cat->types->contains('name', $typeName)))
                                                            <label class="flex items-center gap-1 bg-white px-2 py-0.5 rounded border border-gray-200 cursor-pointer text-[0.7rem] hover:bg-blue-50">
                                                                <input type="checkbox" :value="{{ $cat->id }}" x-model="tempValue" class="size-3 rounded border-gray-300 text-blue-600">
                                                                {{ $cat->name }}
                                                            </label>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="flex justify-end gap-2 pt-2 border-t mt-2">
                                            <button @click="cancelEdit()" class="px-2 py-1 text-xs text-gray-500 bg-gray-200 rounded">Cancel</button>
                                            <button @click="save('categories')" class="px-2 py-1 text-xs text-white bg-blue-600 rounded">Save Categories</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Media Gallery -->
                                <div class="md:col-span-2 border border-gray-100 rounded-lg p-3 bg-white shadow-sm">
                                    <p class="text-[0.65rem] font-bold text-gray-400 uppercase tracking-wider mb-3">Media Gallery</p>
                                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                                        <template x-for="item in media" :key="item.id">
                                            <div class="relative group/media aspect-square bg-gray-100 rounded border overflow-hidden">
                                                <template x-if="item.type === 'video'">
                                                    <div class="w-full h-full flex items-center justify-center bg-gray-900">
                                                        <svg class="w-8 h-8 text-white/50" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.333-5.89a1.5 1.5 0 000-2.538L6.3 2.841z"/></svg>
                                                    </div>
                                                </template>
                                                <template x-if="item.type !== 'video'">
                                                    <img :src="item.path.startsWith('http') ? item.path : '/storage/' + item.path" :alt="item.alt_text" class="w-full h-full object-cover">
                                                </template>
                                                <button @click="removeMedia(item.id)" class="absolute top-1 right-1 bg-red-600 text-white p-1 rounded-full opacity-0 group-hover/media:opacity-100 transition-opacity">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                        
                                        <!-- Add Media Tool -->
                                        <label class="relative aspect-square flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded hover:border-blue-400 hover:bg-blue-50 cursor-pointer transition-colors group/add">
                                            <svg class="w-6 h-6 text-gray-300 group-hover/add:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <span class="text-[0.6rem] text-gray-400 mt-1 uppercase">Add</span>
                                            <input type="file" class="hidden" @change="addMedia">
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between items-center bg-gray-50/50 -mx-4 -mb-4 px-4 py-3 rounded-b-lg">
                                <div class="flex items-center gap-3">
                                        <span class="px-2 py-0.5 rounded" :class="product.approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" x-text="product.approved ? 'Approved' : 'Pending Approval'"></span>
                                    </div>
                                    <a :href="'/products/' + product.id + '/edit'" class="text-xs text-blue-600 hover:underline font-bold">Standard Edit Form &rarr;</a>
                                </div>
                            </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <script>
        function productEditor(config) {
            return {
                product: config.product,
                categories: config.categories,
                techStacks: config.tech_stacks,
                media: config.media || [],
                editingField: null,
                tempValue: null,
                loading: false,
                logoUrl: null,

                // Lookup data injected from server
                allCategories: @js($allCategories->mapWithKeys(fn($c) => [$c->id => ['name' => $c->name, 'types' => $c->types->pluck('name')]])),
                allTechStacks: @js($allTechStacks->pluck('name', 'id')),

                startEdit(field, value) {
                    this.editingField = field;
                    // Handle deep copy for arrays
                    this.tempValue = Array.isArray(value) ? JSON.parse(JSON.stringify(value)) : value;
                },

                cancelEdit() {
                    this.editingField = null;
                    this.tempValue = null;
                },

                getCategoryName(id) { return this.allCategories[id]?.name || 'Unknown'; },
                getTechName(id) { return this.allTechStacks[id] || 'Unknown'; },

                getSoftwareCats() {
                    return this.categories.filter(id => {
                        const types = this.allCategories[id]?.types || [];
                        return types.includes('Software') || types.includes('Category') || types.length === 0;
                    });
                },
                getPricingCats() {
                    return this.categories.filter(id => (this.allCategories[id]?.types || []).includes('Pricing'));
                },
                getBestForCats() {
                    return this.categories.filter(id => (this.allCategories[id]?.types || []).includes('Best for'));
                },

                async save(field) {
                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/inline-update`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ field, value: this.tempValue })
                        });

                        const data = await response.json();
                        if (data.success) {
                            if (field === 'categories') this.categories = JSON.parse(JSON.stringify(this.tempValue));
                            else if (field === 'tech_stacks') this.techStacks = JSON.parse(JSON.stringify(this.tempValue));
                            else if (field === 'maker_links') this.product.maker_links = JSON.parse(JSON.stringify(this.tempValue));
                            else this.product[field] = this.tempValue;

                            this.editingField = null;
                            if (data.message.includes('review')) {
                                alert(data.message);
                                location.reload();
                            }
                        } else {
                            alert(data.message || 'Update failed');
                        }
                    } catch (error) {
                        alert('Something went wrong. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },

                async saveSelling() {
                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/inline-update`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ 
                                field: 'sell_product', 
                                value: this.tempValue.sell_product 
                            })
                        });

                        const priceResponse = await fetch(`/products/${this.product.id}/inline-update`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ 
                                field: 'asking_price', 
                                value: this.tempValue.asking_price 
                            })
                        });

                        this.product.sell_product = this.tempValue.sell_product;
                        this.product.asking_price = this.tempValue.asking_price;
                        this.editingField = null;
                        
                    } catch (error) {
                        alert('Update failed');
                    } finally {
                        this.loading = false;
                    }
                },

                async uploadLogo(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('logo', file);

                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/inline-update-logo`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.product.logo_url = data.logo_url;
                            if (data.message.includes('review')) alert(data.message);
                        } else {
                            alert(data.message || 'Upload failed');
                        }
                    } catch (error) {
                        alert('Error uploading logo');
                    } finally {
                        this.loading = false;
                    }
                },

                async addMedia(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('media', file);

                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/add-media`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.media.push(data.media);
                        } else {
                            alert(data.message || 'Media upload failed');
                        }
                    } catch (error) {
                        alert('Error uploading media');
                    } finally {
                        this.loading = false;
                    }
                },

                async removeMedia(mediaId) {
                    if (!confirm('Are you sure you want to remove this media?')) return;

                    this.loading = true;
                    try {
                        const response = await fetch(`/products/${this.product.id}/remove-media/${mediaId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.media = this.media.filter(m => m.id !== mediaId);
                        } else {
                            alert(data.message || 'Removal failed');
                        }
                    } catch (error) {
                        alert('Error removing media');
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
@push('styles')
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-toolbar.ql-snow { border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem; background: #f9fafb; border-color: #d1d5db; }
        .ql-container.ql-snow { border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; border-color: #d1d5db; }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
@endpush
@endsection