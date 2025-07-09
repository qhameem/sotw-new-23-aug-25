@extends('layouts.app') {{-- Assuming you have a main app layout --}}

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold text-gray-900  mb-2">Review Edits for: {{ $product->name }}</h1>
    <p class="text-sm text-gray-600  mb-6">Submitted by: {{ $product->user->name ?? 'N/A' }}</p>

    <div class="bg-white  shadow overflow-hidden sm:rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Current Live Version --}}
            <div>
                <h2 class="text-xl font-medium text-gray-800  mb-3 border-b pb-2 border-gray-300 ">Current Live Version</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Logo</dt>
                        <dd class="mt-1 text-sm text-gray-900 ">
                            @if($product->logo)
                                <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}" alt="Current Logo" class="w-24 h-24 object-contain rounded border ">
                            @else
                                <span class="text-gray-400 italic">No logo</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Tagline</dt>
                        <dd class="mt-1 text-sm text-gray-900 ">{{ $product->tagline }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900  prose prose-sm  max-w-none">{!! nl2br(e($product->description)) !!}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Categories</dt>
                        <dd class="mt-1 text-sm text-gray-900 ">
                            @forelse($product->categories as $category)
                                <span class="inline-block bg-gray-100  text-gray-700  px-2 py-0.5 text-xs rounded-full mr-1 mb-1">{{ $category->name }}</span>
                            @empty
                                <span class="text-gray-400 italic">No categories</span>
                            @endforelse
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Proposed Edits --}}
            <div>
                <h2 class="text-xl font-medium text-yellow-600  mb-3 border-b pb-2 border-yellow-400 ">Proposed Edits</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Logo</dt>
                        <dd class="mt-1 text-sm text-gray-900 ">
                            @if($product->proposed_logo_path)
                                <img src="{{ asset('storage/' . $product->proposed_logo_path) }}" alt="Proposed Logo" class="w-24 h-24 object-contain rounded border border-yellow-400 ">
                                @if(!$product->logo && $product->proposed_logo_path) <span class="text-xs text-green-500">(New logo proposed)</span> @endif
                            @elseif(is_null($product->proposed_logo_path) && $product->logo && $product->has_pending_edits)
                                <span class="text-red-500 italic">Propose to remove logo</span>
                            @else
                                <span class="text-gray-400 italic">No change to logo proposed</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Tagline</dt>
                        <dd class="mt-1 text-sm text-gray-900  @if($product->proposed_tagline !== $product->tagline) bg-yellow-100  p-1 rounded @endif">{{ $product->proposed_tagline ?? '(No change)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900  prose prose-sm  max-w-none @if($product->proposed_description !== $product->description) bg-yellow-100  p-1 rounded @endif">{!! nl2br(e($product->proposed_description ?? '(No change)')) !!}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 ">Categories</dt>
                        <dd class="mt-1 text-sm text-gray-900  @if($product->proposedCategories->pluck('id')->diff($product->categories->pluck('id'))->isNotEmpty() || $product->categories->pluck('id')->diff($product->proposedCategories->pluck('id'))->isNotEmpty()) bg-yellow-100  p-1 rounded @endif">
                            @forelse($product->proposedCategories as $category)
                                <span class="inline-block bg-yellow-200  text-yellow-800  px-2 py-0.5 text-xs rounded-full mr-1 mb-1">{{ $category->name }}</span>
                            @empty
                                <span class="text-gray-400 italic">No categories proposed (or propose to remove all)</span>
                            @endforelse
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200  flex items-center justify-end space-x-3">
            <form action="{{ route('admin.products.reject-edits', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject these edits? The proposed changes will be discarded.');">
                @csrf
                <button type="submit" class="px-4 py-2 border border-gray-300  rounded-md shadow-sm text-sm font-medium text-gray-700  bg-white  hover:bg-gray-50  focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Reject Edits
                </button>
            </form>
            <form action="{{ route('admin.products.approve-edits', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to approve these edits? The live product will be updated.');">
                @csrf
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Approve Edits
                </button>
            </form>
        </div>
    </div>
</div>
@endsection