@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 sm:px-8" x-data="{
    editingName: false,
    editingTagline: false,
    editingProductPageTagline: false,
    editingDescription: false,
    name: '{{ $product->name }}',
    tagline: '{{ $product->tagline }}',
    product_page_tagline: '{{ $product->product_page_tagline }}',
    description: `{{ $product->description }}`,
    updateProduct() {
        fetch('{{ route('admin.products.update', ['product' => $product->id]) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                name: this.name,
                tagline: this.tagline,
                product_page_tagline: this.product_page_tagline,
                description: this.description
            })
        }).then(response => {
            if (response.ok) {
                window.location.reload();
            }
        });
    }
}">
    <div class="py-8">
        <div>
            <h2 class="text-2xl font-semibold leading-tight">
                <span x-show="!editingName" @click="editingName = true" x-text="name"></span>
                <input x-show="editingName" x-model="name" @keydown.enter="updateProduct(); editingName = false" @keydown.escape="editingName = false" class="form-input">
            </h2>
        </div>
        <div class="my-5">
            <p class="text-gray-600">
                <strong>Tagline:</strong>
                <span x-show="!editingTagline" @click="editingTagline = true" x-text="tagline"></span>
                <input x-show="editingTagline" x-model="tagline" @keydown.enter="updateProduct(); editingTagline = false" @keydown.escape="editingTagline = false" class="form-input">
            </p>
            <p class="text-gray-600">
                <strong>Product Page Tagline:</strong>
                <span x-show="!editingProductPageTagline" @click="editingProductPageTagline = true" x-text="product_page_tagline"></span>
                <input x-show="editingProductPageTagline" x-model="product_page_tagline" @keydown.enter="updateProduct(); editingProductPageTagline = false" @keydown.escape="editingProductPageTagline = false" class="form-input">
            </p>
        </div>
        <div class="prose max-w-none">
            <div x-show="!editingDescription" @click="editingDescription = true" x-html="description"></div>
            <textarea x-show="editingDescription" x-model="description" @keydown.enter="updateProduct(); editingDescription = false" @keydown.escape="editingDescription = false" class="form-input" rows="10"></textarea>
        </div>
        <div class="mt-8">
            <a href="{{ route('admin.products.index') }}" class="text-indigo-600 hover:text-indigo-900">Back to products</a>
        </div>
    </div>
</div>
@endsection