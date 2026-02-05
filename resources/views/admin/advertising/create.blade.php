@extends('layouts.app')

@section('title', 'Create Advertisement')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush


@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Create Sponsor</h1>

        <form action="{{ route('admin.advertising.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="bg-white shadow-md rounded-lg p-6">
                <div class="mb-4">
                    <label for="internal_name" class="block text-gray-700 text-sm font-bold mb-2">Sponsor Name:</label>
                    <input type="text" name="internal_name" id="internal_name"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                </div>

                <div class="mb-4">
                    <label for="tagline" class="block text-gray-700 text-sm font-bold mb-2">Tagline:</label>
                    <input type="text" name="tagline" id="tagline"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                </div>

                <div class="mb-4">
                    <label for="target_url" class="block text-gray-700 text-sm font-bold mb-2">Target URL:</label>
                    <input type="url" name="target_url" id="target_url"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        required>
                </div>

                <div class="mb-4">
                    <label for="logo" class="block text-gray-700 text-sm font-bold mb-2">Logo:</label>
                    <input type="file" name="logo" id="logo"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <img id="logo_preview" src="" alt="Logo Preview" class="mt-2 w-20 h-20 object-cover rounded-lg"
                        style="display: none;">
                </div>

                <div class="mb-4">
                    <label for="product_id" class="block text-gray-700 text-sm font-bold mb-2">Or Select Existing
                        Product:</label>
                    <select name="product_id" id="product_id"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select a product</option>
                        @foreach(\App\Models\Product::latest()->get() as $product)
                            <option value="{{ $product->id }}" data-tagline="{{ $product->tagline }}"
                                data-url="{{ $product->link }}" data-logo="{{ $product->logo_url }}">{{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="ad_zone_id"
                    value="{{ \App\Models\AdZone::where('slug', 'sponsors')->first()?->id }}">
                <input type="hidden" name="type" value="image_banner">

                <div class="flex items-center justify-between">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Create Sponsor
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#product_id').select2({
                templateResult: formatProduct,
                templateSelection: formatProductSelection
            });

            $('#product_id').on('select2:select', function (e) {
                var data = e.params.data;
                var selectedOption = data.element;
                var nameInput = document.getElementById('internal_name');
                var taglineInput = document.getElementById('tagline');
                var urlInput = document.getElementById('target_url');
                var logoInput = document.getElementById('logo');
                var logoPreview = document.getElementById('logo_preview');

                if (this.value) {
                    nameInput.value = data.text;
                    taglineInput.value = $(selectedOption).data('tagline');
                    urlInput.value = $(selectedOption).data('url');
                    logoPreview.src = $(selectedOption).data('logo');
                    logoPreview.style.display = 'block';

                    nameInput.readOnly = true;
                    taglineInput.readOnly = true;
                    urlInput.readOnly = true;
                    logoInput.disabled = true;
                    nameInput.required = false;
                    taglineInput.required = false;
                    urlInput.required = false;
                    logoInput.required = false;
                } else {
                    nameInput.value = '';
                    taglineInput.value = '';
                    urlInput.value = '';
                    logoPreview.src = '';
                    logoPreview.style.display = 'none';

                    nameInput.readOnly = false;
                    taglineInput.readOnly = false;
                    urlInput.readOnly = false;
                    logoInput.disabled = false;
                    nameInput.required = true;
                    taglineInput.required = true;
                    urlInput.required = true;
                    logoInput.required = true;
                }
            });
        });

        function formatProduct(product) {
            if (!product.id) {
                return product.text;
            }

            var $product = $(
                '<div class="flex items-center">' +
                '<img src="' + $(product.element).data('logo') + '" class="w-10 h-10 mr-2 rounded" />' +
                '<div>' +
                '<div class="font-semibold">' + product.text + '</div>' +
                '<div class="text-sm text-gray-500">' + $(product.element).data('tagline') + '</div>' +
                '<div class="text-xs text-gray-400">' + $(product.element).data('url') + '</div>' +
                '</div>' +
                '</div>'
            );

            return $product;
        }

        function formatProductSelection(product) {
            return product.text;
        }
    </script>
@endpush