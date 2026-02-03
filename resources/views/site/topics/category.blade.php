@extends('layouts.app')

@section('title', $meta_title ?? 'Software on the Web')

@section('meta_description')
    @if(!empty(trim($category->meta_description)))
        <meta name="description" content="{{ $category->meta_description }}">
    @endif
@endsection


@section('content')
    <x-main-content-layout>

        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-2 md:py-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 md:gap-6">
                <section class="md:col-span-12 col-span-1 min-w-0">
                    @if(!empty(trim($category->description)))
                        <p class="pl-4 mb-4 text-gray-700 ">{{ $category->description }}</p>
                    @endif

                    <div class="md:space-y-1">
                        @include('partials.products_list', [
                            'promotedProducts' => $promotedProducts,
                            'regularProducts' => $regularProducts,
                            'belowProductListingAd' => $belowProductListingAd ?? null,
                            'belowProductListingAdPosition' => $belowProductListingAdPosition ?? null
                        ])
                    </div>

                    @if($promotedProducts->isEmpty() && $regularProducts->isEmpty())
                        <div class="text-gray-400 text-center py-12">No products found in this category.</div>
                    @endif
                </section>
            </div>
        </div>
    </x-main-content-layout>
@endsection