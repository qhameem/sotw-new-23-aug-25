@extends('layouts.app')

@section('header-title')
    <div class="flex gap-4 justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-700 py-[1px]">
            {{ isset($product) ? 'Edit Product: ' . ($displayData['name'] ?? $product->name) : 'Add Your Product' }}
        </h1>
    </div>
@endsection


@section('content')
<div class="relative">
    @guest
    <div class="mt-10 inset-0 bg-white bg-opacity-75 z-10 flex items-center justify-center">
        <div class="text-center p-8 bg-white border rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Please log in to add your product</h2>
            <p class="text-gray-600 mb-4 text-sm tracking-tight">Join our community and showcase your product to a wider audience.</p>
            <button @click.prevent="$dispatch('open-login-modal')" class="bg-primary-500 text-white font-semibold text-sm hover:bg-primary-600 transition-colors duration-200 py-1 px-4 rounded-md hover:opacity-90">
                Log in or Sign up &rarr;
            </button>
        </div>
    </div>
    @endguest
    <div class="mx-auto px-4 sm:px-6 lg:px-2 py-6 pb-24 @guest blur-sm pointer-events-none @endguest">
        @if(isset($product))
            <div class="mb-4 p-3 rounded-md bg-blue-50 border border-blue-300 text-blue-700 text-sm">
                <strong>Note:</strong> Product Name, URL, and Slug cannot be changed through this form. To request changes to these fields, please contact support (support system to be implemented).
            </div>
        @endif

        @if(isset($product) && $product->approved && $product->has_pending_edits)
            <div class="mb-4 p-3 rounded-md bg-yellow-50 border border-yellow-400 text-yellow-800 text-sm">
                <strong>Pending Review:</strong> You have submitted edits for this product that are currently awaiting administrator approval. The changes you make below will update your pending proposal. The live product will not change until an admin approves your edits.
            </div>
        @endif
       
        @if($errors->any())
            <div class="bg-red-100 text-red-800 p-2 rounded mb-4">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="flex flex-col md:flex-row gap-6">
            <div class="md:w-full">
                <div id="product-submit-app" x-ignore></div>
            </div>
        </div>
   </div>
</div>
@endsection
