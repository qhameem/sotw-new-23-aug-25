@extends('layouts.app')

@section('title', 'Product Submission Complete')

@section('header-title')
    <h1 class="sm:text-sm md:text-base font-bold text-gray-800 py-[3px]">Product Submission Complete</h1>
@endsection

@section('actions')
<x-add-product-button />
@endsection

@section('content')
    <div class="p-4 mx-auto max-w-7xl">
        <div class="flex items-center p-4 mb-6 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50" role="alert">
            <div class="px-3"><x-simpleline-check class="w-4 h-4" /></div>
            <div>
                <span class="font-semibold">Thank you! Your product has been submitted for review.</span>
            </div>
        </div>

<h3 class="mb-5 font-noto-serif text-lg font-medium text-gray-800 italic">What's next?</h3>

        <div class="space-y-6">

             <x-promote.fast-track-card />
<div class="flex flex-col gap-2">
    <div>
           <x-promote.premium-spot-card :spots-available="$spotsAvailable" />
    </div>
    <div>
         <x-promote.product-review-card />
    </div>

</div>
     


           
            
        </div>
    </div>
    {{-- Mobile view for "What's next?" --}}
    <div class="md:hidden mt-8">
       
        <div class="space-y-3">
            <a href="{{ route('products.my') }}" class="block text-center w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150 py-2">
                Show all my submissions
            </a>
            @if(isset($product))
                <a href="{{ route('products.edit', $product) }}" class="block text-center w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150 py-2">
                    Edit this submission
                </a>
            @endif
        </div>
    </div>
@endsection

@section('right_sidebar_content')
    <div class="p-6 items-start hidden md:block">
        <h3 class="font-noto-serif text-lg font-medium tracking-tighter italic text-gray-800 mb-4">Other options</h3>
        <div class="space-y-2">
       
            <a href="{{ route('products.my') }}" class="block w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150 py-2">
                Show all my submissions
            </a>
            @if(isset($product))
                <a href="{{ route('products.edit', $product) }}" class="block w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150">
                    Edit this submission
                </a>
            @endif
        </div>
    </div>
@endsection