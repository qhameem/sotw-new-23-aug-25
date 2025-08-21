@extends('layouts.app')

@section('header-title', 'Product Approvals')

@section('actions')
    <x-add-product-button />
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @if(session('success'))
        <div class="mb-4 text-green-700 bg-green-100 rounded p-2">{{ session('success') }}</div>
    @endif
    <div class="mb-10">
        <h2 class="text-lg font-semibold mb-3">Pending Approval</h2>
        @if($pendingProducts->count() > 0)
            {{-- Bulk Approve Form (moved outside individual product cards) --}}
            <form action="{{ route('admin.product-approvals.bulk-approve') }}" method="POST" id="bulk-approve-form">
                @csrf
                <div class="mb-4 flex items-center gap-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" id="select-all" class="mr-2 h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <span class="text-sm font-medium text-gray-700">Select All</span>
                    </label>
                    <x-scheduled-datepicker name="bulk_published_at" />
                    <button type="submit" class="px-4 py-1 border border-sky-500 text-sky-600 rounded-md hover:bg-sky-50 text-sm font-medium">Approve Selected</button>
                </div>
            </form>
            <div class="space-y-6">
                @foreach($pendingProducts as $product)
                    @include('admin.product_approvals._product_approval_card', ['product' => $product])
                @endforeach
            </div>
        @else
            <div class="text-gray-500 text-center py-10">
                <p>No products are currently pending approval.</p>
            </div>
        @endif
    </div>
    <div>
        <h2 class="text-lg font-semibold mb-3">Approved Products</h2>

        <div class="mb-4 flex justify-between items-center">
            <div>
                <form method="GET" action="{{ route('admin.product-approvals.index') }}" class="inline-flex items-center">
                    <label for="per_page" class="mr-2 text-sm">Show:</label>
                    <select name="per_page" id="per_page" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    <input type="hidden" name="sort_direction" value="{{ $sortDirection }}">
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border rounded">
                <thead>
                    <tr>
                        @php
                            $linkParams = ['per_page' => $perPage];
                            $sortArrow = fn($column) => ($sortBy === $column ? ($sortDirection === 'asc' ? '&uarr;' : '&darr;') : '');
                        @endphp
                        <th class="px-4 py-2 border-b text-left">
                            <a href="{{ route('admin.product-approvals.index', array_merge($linkParams, ['sort_by' => 'name', 'sort_direction' => $sortBy === 'name' && $sortDirection === 'asc' ? 'desc' : 'asc'])) }}" class="hover:underline">
                                Product {!! $sortArrow('name') !!}
                            </a>
                        </th>
                        <th class="px-4 py-2 border-b text-left">User</th>
                        <th class="px-4 py-2 border-b text-left">Categories</th>
                        <th class="px-4 py-2 border-b text-left">
                             <a href="{{ route('admin.product-approvals.index', array_merge($linkParams, ['sort_by' => 'published_at', 'sort_direction' => $sortBy === 'published_at' && $sortDirection === 'asc' ? 'desc' : 'asc'])) }}" class="hover:underline">
                                Publish Date {!! $sortArrow('published_at') !!}
                            </a>
                        </th>
                        <th class="px-4 py-2 border-b text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvedProducts as $product)
                        <tr>
                            <td class="px-4 py-2 border-b align-top">
                                <div class="flex items-center">
                                    <img src="{{ $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link) }}" alt="Logo" class="w-8 h-8 rounded object-cover bg-gray-100 flex-shrink-0 border mr-3">
                                    <div>
                                        <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow" class="font-semibold hover:underline">{{ $product->name }}</a>
                                        <p class="text-xs text-gray-600">{{ Str::limit($product->tagline, 70) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b align-top">{{ $product->user->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border-b align-top">
                                @foreach($product->categories as $cat)
                                    <span class="inline-block bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs mr-1 mb-1">{{ $cat->name }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-2 border-b align-top">
                                {{ $product->published_at ? $product->published_at->format('M d, Y') : 'Not Set' }}
                                @if($product->published_at && $product->published_at->isFuture())
                                    <span class="text-xs text-blue-500 block">(Scheduled)</span>
                                @elseif($product->published_at)
                                    <span class="text-xs text-green-500 block">(Published)</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b align-top">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="text-indigo-600 hover:underline text-sm">Edit</a>
                                    <form action="{{ route('admin.product-approvals.disapprove', $product) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:underline text-sm" onclick="return confirm('Are you sure you want to disapprove this product?')">Disapprove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-gray-400 text-center py-4">No approved products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            {{ $approvedProducts->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    });
});
</script>
@endpush
