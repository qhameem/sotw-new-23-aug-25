@extends('layouts.app') {{-- Assuming you have a main app layout --}}

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold text-gray-900  mb-6">Products with Pending Edits</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if($productsWithPendingEdits->isEmpty())
        <p class="text-gray-700 ">There are no products currently awaiting review for their edits.</p>
    @else
        <div class="overflow-x-auto bg-white shadow sm:rounded-lg">
            <table class="w-full table-fixed divide-y divide-gray-200">
                <thead class="bg-gray-50 ">
                    <tr>
                        <th scope="col" class="w-5/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product Name</th>
                        <th scope="col" class="w-2/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Submitted By</th>
                        <th scope="col" class="w-3/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Edit Proposed</th>
                        <th scope="col" class="w-2/12 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white  divide-y divide-gray-200 ">
                    @foreach ($productsWithPendingEdits as $product)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <a href="{{ $product->link }}" target="_blank" rel="{{ \App\Support\OutboundLink::rel($product->link, 'product_link') }}" class="hover:underline">{{ $product->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $product->user->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $product->updated_at->format('M d, Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <a href="{{ route('admin.products.review-edits', $product) }}" class="inline-flex whitespace-nowrap rounded-md bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700">Review Edits</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
