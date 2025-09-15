@extends('layouts.app')


@section('header-title')
    Products
@endsection

@section('actions')
    <div class="md:flex items-center space-x-2">
        <x-add-product-button />
    </div>
@endsection

@section('content')
    <div class="p-4">
        @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4 shadow">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4 shadow">{{ session('error') }}</div>
        @endif

        <!-- Search and Bulk Actions -->
        <div class="mb-4 md:flex justify-between items-center">
            <div class="mb-4 md:mb-0">
                <form action="{{ route('admin.products.index') }}" method="GET" class="flex items-center">
                    <input type="text" name="q" value="{{ $searchTerm ?? '' }}" placeholder="Search products..." class="border rounded-l px-4 py-2 w-full focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-r">Search</button>
                    @if($searchTerm ?? null)
                    <a href="{{ route('admin.products.index') }}" class="ml-2 text-sm text-gray-600 hover:text-gray-800">Clear</a>
                    @endif
                </form>
            </div>
            <div>
                <form id="bulk-delete-form" action="{{ url('/temporary-bulk-delete-test-no-name') }}" method="POST">
                    @csrf
                    <div class="flex items-center">
                        <input type="checkbox" id="select-all-products" class="rounded mr-2">
                        <button type="submit" id="bulk-delete-button" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50" disabled>
                            Delete Selected (<span id="selected-count">0</span>)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products List -->
        <div class="bg-white rounded-lg shadow-md">
            @forelse($products as $product)
                <x-admin.product-list-item :product="$product" />
            @empty
                <div class="text-center py-12">
                    <p class="text-gray-500">No products found.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('select-all-products');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkDeleteButton = document.getElementById('bulk-delete-button');
    const selectedCountSpan = document.getElementById('selected-count');

    function updateSelectedCount() {
        const count = document.querySelectorAll('.product-checkbox:checked').length;
        selectedCountSpan.textContent = count;
        bulkDeleteButton.disabled = count === 0;
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedCount();
        });
    }

    if (productCheckboxes.length > 0) {
        productCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    let allChecked = true;
                    productCheckboxes.forEach(cb => {
                        if (!cb.checked) {
                            allChecked = false;
                        }
                    });
                    selectAllCheckbox.checked = allChecked;
                }
                updateSelectedCount();
            });
        });
    }

    updateSelectedCount();
});
</script>
@endpush