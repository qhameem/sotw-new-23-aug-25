@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 sm:px-8">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight">{{ $product->name }}</h2>
        </div>
        <div class="my-5">
            <p class="text-gray-600"><strong>Tagline:</strong> {{ $product->tagline }}</p>
            <p class="text-gray-600"><strong>Product Page Tagline:</strong> {{ $product->product_page_tagline }}</p>
        </div>
        <div class="prose max-w-none">
            {!! $product->description !!}
        </div>
        <div class="mt-8">
            <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-900">Back to products</a>
        </div>
    </div>
</div>
@endsection