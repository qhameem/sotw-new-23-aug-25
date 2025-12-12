@extends('layouts.app')

@section('header-title')
    <div class="flex gap-4 justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-700 py-[1px]">
            Edit Product: {{ $product->name }}
        </h1>
    </div>
@endsection

@section('content')
<div class="relative">
    <div class="mx-auto px-4 sm:px-6 lg:px-2 py-6 pb-24">
        @if(session('success'))
            <div class="mb-4 text-green-700 bg-green-100 rounded p-2">{{ session('success') }}</div>
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
                @include('products.partials._form', [
                    'displayData' => $displayData,
                    'regularCategories' => $regularCategories,
                    'bestForCategories' => $bestForCategories,
                    'pricingCategories' => $pricingCategories,
                    'allTechStacksData' => $allTechStacksData,
                    'product' => $product
                ])
           </div>
       </div>
   </div>
</div>
@endsection
