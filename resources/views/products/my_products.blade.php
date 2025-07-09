@extends('layouts.app')

@section('title')
    <h1 class="text-xl font-bold text-gray-800">My Submitted Products</h1>
@endsection

@section('actions')
    <x-add-product-button />
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
                    <article class="bg-white p-4 md:p-4 border rounded-lg flex gap-4 md:gap-6 items-start" itemscope itemtype="https://schema.org/Product">
                        <img src="{{ $logo ?? $favicon }}" alt="{{ $product->name }} logo" class="w-8 h-8 md:w-10 md:h-10 rounded object-cover flex-shrink-0" loading="lazy" itemprop="image" />
                        <div class="flex-1">
                            <h2 class="text-base md:text-lg font-bold leading-tight mb-1" itemprop="name">
                                <a href="{{ $product->link . (parse_url($product->link, PHP_URL_QUERY) ? '&' : '?') }}utm_source=softwareontheweb.com" target="_blank" rel="noopener nofollow" class="hover:underline" itemprop="url">{{ $product->name }}</a>
                            </h2>
                            <div class="mt-2 space-y-3">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">Tagline</p>
                                    <p class="text-gray-700 text-sm" itemprop="tagline">{{ $product->tagline }}</p>
                                </div>
                                @if($product->product_page_tagline)
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">Product Page Tagline</p>
                                    <p class="text-gray-700 text-sm">{{ $product->product_page_tagline }}</p>
                                </div>
                                @endif
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">Description</p>
                                    <div class="prose prose-sm text-sm max-w-none text-gray-600" itemprop="description">
                                        {!! $product->description !!}
                                    </div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 mb-2 mt-2">
                                Submitted: {{ $product->created_at->format('M d, Y') }}
                            </div>
                            <div class="mb-3">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full
                                    @if($product->approved)
                                        bg-green-100 text-green-700
                                    @else
                                        bg-yellow-100 text-yellow-700
                                    @endif">
                                    {{ $product->approved ? 'Approved' : 'Pending Approval' }}
                                </span>
                            </div>
                            <div class="mt-1 flex flex-wrap gap-2 items-center">
                                @foreach($product->categories as $cat)
                                <a href="{{ route('categories.show', ['category' => $cat->slug]) }}"
                                       @click.stop
                                       class="hidden sm:block inline-flex items-center text-gray-600  hover:text-gray-800 rounded text-xs">
                                        <span class="px-0 py-0 hover:underline">{{ $cat->name }}</span>
                                        @if(isset($cat->products_count))
                                        <span class="ml-1.5 mr-2 h-5 w-5 min-w-[1.25rem] rounded-full bg-gray-200 hover:bg-gray-300 text-gray-500 hover:text-gray-600 text-xs font-semibold flex items-center justify-center leading-none antialiased">
                                            {{ $cat->products_count > 99 ? '99+' : $cat->products_count }}
                                        </span>
                                        @endif
                                    </a>
                                 @if(!$loop->last)
                                    <span class="text-gray-400">•</span>
                                @endif
                                @endforeach
                            </div>
                            @if(Auth::id() == $product->user_id)
                                <div class="mt-3">
                                    <a href="{{ route('products.edit', $product->id) }}" class="text-sm text-blue-600 hover:underline font-medium">
                                        Edit Product
                                    </a>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection