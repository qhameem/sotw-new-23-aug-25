@extends('layouts.app')

@section('title', $product->name . ' - Software on the Web')
@section('description', $product->product_page_tagline)

@section('content')
<x-main-content-layout>
    <x-slot:title>
        <div class="flex items-center">
            @if($product->logo)
                <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="{{ $product->name }} logo" class="w-8 h-8 object-contain rounded-lg mr-3">
            @elseif($product->link)
                <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="w-8 h-8 object-contain rounded-lg mr-3">
            @endif
            <h1 class="text-lg font-semibold tracking-tight">{{ $product->name }}</h1>
        </div>
    </x-slot:title>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-white rounded-lg max-w-3xl mx-auto p-6 md:p-8">
            {{-- Product Header: Logo, Name, Tagline --}}
            <div class="flex flex-col items-center text-center mb-4">
                {{-- Logo --}}
                <div class="mb-4">
                    @if($product->logo)
                        <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="{{ $product->name }} logo" class="w-16 h-16 md:w-16 md:h-16 object-contain rounded-lg mx-auto">
                    @elseif($product->link)
                        <img src="{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="{{ $product->name }} favicon" class="w-16 h-16 md:w-16 md:h-16 object-contain rounded-lg mx-auto">
                    @else
                        <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-200 rounded-lg flex items-center justify-center mx-auto">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    @endif
                </div>
                {{-- Name and Tagline --}}
                <div>
                    <h1 class="text-lg md:text-gl font-semibold text-gray-900">{{ $product->name }}</h1>
                    <p class="text-gray-900 mt-1 text-sm">{{ $product->product_page_tagline }}</p>
                </div>
            </div>
            
            <!-- Product link -->
            <div class="flex justify-center">
                 <a href="{{ $product->link . (strpos($product->link, '?') === false ? '?' : '&') }}utm_source=softwareontheweb.com"
                    target="_blank" rel="noopener nofollow noreferrer"
                    class="">
                    <div class="flex flex-row items-center">
                        <div>
                             <svg class="w-5 h-5" viewBox="0 -0.5 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.2"></g><g id="SVGRepo_iconCarrier"> <path d="M11.1828 7.68276C10.8899 7.97566 10.8899 8.45053 11.1828 8.74342C11.4756 9.03632 11.9505 9.03632 12.2434 8.74342L11.1828 7.68276ZM13.9851 5.94109L14.5154 6.47142V6.47142L13.9851 5.94109ZM18.5291 5.94109L17.9988 6.47142L18.5291 5.94109ZM18.5291 10.4851L17.9988 9.95476L18.5291 10.4851ZM15.7268 12.2268C15.4339 12.5197 15.4339 12.9945 15.7268 13.2874C16.0196 13.5803 16.4945 13.5803 16.7874 13.2874L15.7268 12.2268ZM13.7583 16.3185C14.0513 16.0257 14.0514 15.5508 13.7585 15.2579C13.4657 14.9649 12.9908 14.9648 12.6979 15.2576L13.7583 16.3185ZM10.9561 18.0591L11.4797 18.5961L11.4863 18.5895L10.9561 18.0591ZM6.44132 18.0309L5.91104 18.5612H5.91104L6.44132 18.0309ZM6.41208 13.5161L5.88171 12.9857L5.87499 12.9926L6.41208 13.5161ZM9.21441 11.7744C9.50731 11.4815 9.50731 11.0067 9.21441 10.7138C8.92152 10.4209 8.44665 10.4209 8.15375 10.7138L9.21441 11.7744ZM15.2744 10.2574C15.5673 9.96453 15.5673 9.48966 15.2744 9.19676C14.9815 8.90387 14.5066 8.90387 14.2138 9.19676L15.2744 10.2574ZM9.66975 13.7408C9.37686 14.0337 9.37686 14.5085 9.66975 14.8014C9.96265 15.0943 10.4375 15.0943 10.7304 14.8014L9.66975 13.7408ZM12.2434 8.74342L14.5154 6.47142L13.4548 5.41076L11.1828 7.68276L12.2434 8.74342ZM14.5154 6.47142C15.4773 5.50953 17.0369 5.50953 17.9988 6.47142L19.0594 5.41076C17.5117 3.86308 15.0024 3.86308 13.4548 5.41076L14.5154 6.47142ZM17.9988 6.47142C18.9607 7.43332 18.9607 8.99287 17.9988 9.95476L19.0594 11.0154C20.6071 9.46774 20.6071 6.95845 19.0594 5.41076L17.9988 6.47142ZM17.9988 9.95476L15.7268 12.2268L16.7874 13.2874L19.0594 11.0154L17.9988 9.95476ZM12.6979 15.2576L10.4259 17.5286L11.4863 18.5895L13.7583 16.3185L12.6979 15.2576ZM10.4325 17.5221C9.46732 18.4632 7.92491 18.4536 6.97159 17.5005L5.91104 18.5612C7.44495 20.0948 9.92671 20.1103 11.4797 18.5961L10.4325 17.5221ZM6.97159 17.5005C6.01827 16.5474 6.00828 15.0049 6.94918 14.0396L5.87499 12.9926C4.36107 14.5459 4.37714 17.0277 5.91104 18.5612L6.97159 17.5005ZM6.94241 14.0464L9.21441 11.7744L8.15375 10.7138L5.88175 12.9858L6.94241 14.0464ZM14.2138 9.19676L9.66975 13.7408L10.7304 14.8014L15.2744 10.2574L14.2138 9.19676Z" fill="#1a1a1a"></path> </g></svg>
                        </div>
                        <div class="text-xs lowercase">
                              {{ $product->name }} 
                        </div>

                    </div>
                  
                   
                </a>
            </div>


           <hr class="mt-4">
            
            {{-- Description --}}
            <div class="prose max-w-none mb-6">
                {!! $product->description ?: 'No description available.' !!}
            </div>

            @php
                $generalCategories = $product->categories->filter(function ($cat) {
                    return !$cat->types->contains('name', 'Pricing');
                });
                $pricingCategories = $product->categories->filter(function ($cat) {
                    return $cat->types->contains('name', 'Pricing');
                });
            @endphp

            {{-- Software Categories (excluding Pricing) --}}
            @if($generalCategories->count() > 0)
                <div class="mb-6">
                    <h3 class="text-md font-semibold text-gray-800 mb-2">Categories</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($generalCategories as $category)
                            <a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="bg-gray-100 text-gray-700 px-3 py-1 text-xs rounded-sm hover:bg-gray-200">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Pricing Categories --}}
            @if($pricingCategories->count() > 0)
                <div class="mb-6">
                    <h3 class="text-md font-semibold text-gray-800 mb-2">Pricing</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($pricingCategories as $category)
                            <a href="{{ route('categories.show', ['category' => $category->slug]) }}" class="bg-gray-100 text-gray-700 px-3 py-1 text-xs rounded-sm hover:bg-gray-200">{{ $category->name }}</a>
                       @endforeach
                    </div>
                </div>
            @endif

            {{-- Pricing Information (direct fields) --}}
            @if($product->pricing_type || ($product->price && is_numeric($product->price) && $product->price > 0))
                <div class="mb-6">
                    <h3 class="text-md font-semibold text-gray-800 mb-2">Pricing Information:</h3>
                    <div class="flex flex-wrap gap-2 items-center">
                        <span class="text-gray-700 text-sm">
                            @if($product->pricing_type)
                                <span>{{ $product->pricing_type }}</span>
                            @endif
                            @if($product->pricing_type && $product->price && is_numeric($product->price) && $product->price > 0)
                                <span> - </span>
                            @endif
                            @if($product->price && is_numeric($product->price) && $product->price > 0)
                                <span>${{ number_format($product->price, 2) }}</span>
                            @endif
                        </span>
                    </div>
                </div>
            @endif
            
            {{-- Fallback if no category or pricing information is available --}}
            @if($generalCategories->isEmpty() && $pricingCategories->isEmpty() && !($product->pricing_type || ($product->price && is_numeric($product->price) && $product->price > 0)))
                 <p class="text-gray-500 italic mb-6">No category or pricing information available.</p>
            @endif
            
            {{-- Action Buttons --}}
            <div class="mt-8 flex flex-wrap justify-end gap-3">
                 <a href="{{ $product->link . (strpos($product->link, '?') === false ? '?' : '&') }}utm_source=softwareontheweb.com"
                    target="_blank" rel="noopener nofollow noreferrer"
                    class="inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-600 active:bg-primary-700 focus:outline-none focus:border-primary-700 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                    Visit Product Page
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
                @if(Auth::check() && (Auth::id() === $product->user_id || Auth::user()->hasRole('admin')))
                    <a href="{{ route('products.edit', $product) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Edit Product
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-main-content-layout>
@endsection

@push('scripts')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "{{ addslashes($product->name) }}",
  "description": "{{ addslashes($product->product_page_tagline) }}",
  "url": "{{ route('products.show', $product->slug) }}",
  @if($product->logo)
  "image": "{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}",
  @elseif($product->link)
  "image": "{{ 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}",
  @endif
  "sku": "{{ $product->id }}", // Assuming product ID is a SKU
  @if(!empty($product->brand_name))
  "brand": {
    "@type": "Organization",
    "name": "{{ addslashes($product->brand_name) }}"
  },
  @endif
  @php
    $votesCount = $product->votes_count ?? 0; // Use votes_count from the model
  @endphp
  @if($votesCount > 0)
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingCount": "{{ $votesCount }}"
    // You could set a nominal ratingValue if desired, e.g., "5" if all upvoted items are considered positively reviewed.
    // "ratingValue": "5",
    // "bestRating": "5",
    // "worstRating": "0"
    // For now, ratingCount alone might satisfy GSC as it asks for one of offers, review, or aggregateRating.
  },
  @endif
  "offers": {
    "@type": "Offer",
    // URL to the page where the user can obtain the product (vendor's page)
    "url": "{{ $product->link ? addslashes($product->link . (strpos($product->link, '?') === false ? '?' : '&') . 'utm_source=softwareontheweb.com&utm_medium=structured_data') : route('products.show', $product->slug) }}",
    "availability": "https://schema.org/OnlineOnly", // Assuming all products are online
    @if(!empty($product->vendor_name))
    "seller": {
      "@type": "Organization",
      "name": "{{ addslashes($product->vendor_name) }}"
    },
    @endif
    @php
        $price = null;
        $priceCurrency = 'USD'; // Default currency
        $isFree = false;
        // Use the new accessor from the Product model
        $pricingModelDescription = $product->pricing_model_description ?? null;

        // Logic for determining if a product is explicitly free by its pricing model or price
        if ($pricingModelDescription && strtolower($pricingModelDescription) === 'free') {
            $price = "0.00";
            $isFree = true;
        } elseif (isset($product->price) && is_numeric($product->price) && $product->price == 0) {
            $price = "0.00";
            $isFree = true;
            // Ensure pricingModelDescription reflects 'Free' if price is 0 and no other model is set
            if (!$pricingModelDescription) {
                $pricingModelDescription = 'Free';
            }
        } elseif (isset($product->price) && is_numeric($product->price) && $product->price > 0) {
            $price = number_format($product->price, 2, '.', '');
        }


    @endphp
    @if($price !== null)
    "price": "{{ $price }}",
    "priceCurrency": "{{ $priceCurrency }}",
        @if($isFree)
    "isAccessibleForFree": true,
        @endif
    @elseif($pricingModelDescription)
    "priceSpecification": {
        "@type": "PriceSpecification",
        "priceCurrency": "{{ $priceCurrency }}", // Still good to specify currency
        "description": "{{ addslashes($pricingModelDescription) }}"
    }
    @endif
  }
}
</script>
@endpush