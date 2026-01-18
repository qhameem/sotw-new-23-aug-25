@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 mb-1">Review Edits for: {{ $product->name }}</h1>
                <div class="flex flex-col space-y-1">
                    <p class="text-sm text-gray-600">Submitted by: <span
                            class="font-medium text-gray-900">{{ $product->user->name ?? 'N/A' }}</span> on
                        {{ $product->created_at->format('M d, Y') }}</p>
                    @if($product->lastEditor)
                        <p class="text-sm text-blue-600 font-semibold">Last edited by: <span
                                class="text-blue-800">{{ $product->lastEditor->name }}</span>
                            ({{ $product->updated_at->diffForHumans() }})</p>
                    @endif
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.products.pending-edits.index') }}"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Back to List
                </a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 divide-x divide-gray-200">
                {{-- Current Live Version --}}
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 uppercase tracking-tight">
                        Current Live Version</h2>
                    <div class="space-y-6">
                        {{-- Logo --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Logo</label>
                            <div class="mt-1">
                                @if($product->logo)
                                    <img src="{{ Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo) }}"
                                        alt="Current Logo" class="w-20 h-20 object-contain rounded border bg-gray-50">
                                @else
                                    <span class="text-gray-400 italic text-sm">No logo</span>
                                @endif
                            </div>
                        </div>

                        {{-- Basic Info --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Name</label>
                                <p class="text-sm text-gray-900 font-medium">{{ $product->name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Slug</label>
                                <p class="text-sm text-gray-500">{{ $product->slug }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline
                                    (Listing)</label>
                                <p class="text-sm text-gray-900">{{ $product->tagline }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline (Product
                                    Page)</label>
                                <p class="text-sm text-gray-900">{{ $product->product_page_tagline ?? 'Not set' }}</p>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Description</label>
                            <div class="mt-1 text-sm text-gray-700 prose prose-sm max-w-none bg-gray-50 p-3 rounded border">
                                {!! nl2br(e($product->description)) !!}
                            </div>
                        </div>

                        {{-- Links & More --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Product Link</label>
                                <p class="text-sm text-blue-600 break-all"><a href="{{ $product->link }}" target="_blank"
                                        class="hover:underline">{{ $product->link }}</a></p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Video URL</label>
                                <p class="text-sm text-gray-900 break-all">{{ $product->video_url ?? 'None' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">X Account</label>
                                <p class="text-sm text-gray-900">@ {{ $product->x_account ?? 'None' }}</p>
                            </div>
                        </div>

                        {{-- Categories & Tech --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Categories</label>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @forelse($product->categories as $category)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">{{ $category->name }}</span>
                                    @empty
                                        <span class="text-gray-400 italic text-sm">No categories</span>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tech Stacks</label>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @forelse($product->techStacks as $tech)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">{{ $tech->name }}</span>
                                    @empty
                                        <span class="text-gray-400 italic text-sm">None</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Selling Info --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Selling Status</label>
                            <p class="text-sm text-gray-900">
                                {{ $product->sell_product ? 'For Sale' : 'Not for Sale' }}
                                @if($product->sell_product && $product->asking_price)
                                    <span
                                        class="ml-2 font-bold text-green-600">${{ number_format($product->asking_price, 2) }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Proposed Edits --}}
                <div class="p-6 bg-yellow-50/30">
                    <h2
                        class="text-lg font-bold text-yellow-700 mb-4 pb-2 border-b border-yellow-200 uppercase tracking-tight">
                        Proposed Edits (Awaiting Approval)</h2>
                    <div class="space-y-6">
                        {{-- Proposed Logo --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Logo</label>
                            <div class="mt-1">
                                @if($product->proposed_logo_path)
                                    <img src="{{ asset('storage/' . $product->proposed_logo_path) }}" alt="Proposed Logo"
                                        class="w-20 h-20 object-contain rounded border border-yellow-400 bg-white">
                                    <p class="mt-1 text-[0.7rem] text-green-600 font-bold">New logo proposed</p>
                                @elseif(is_null($product->proposed_logo_path) && $product->logo && $product->has_pending_edits)
                                    <div class="p-2 border border-red-200 bg-red-50 rounded text-red-600 text-sm font-semibold">
                                        Propose to REMOVE logo</div>
                                @else
                                    <span class="text-gray-400 italic text-sm">No change proposed</span>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Basic Info --}}
                        <div class="grid grid-cols-1 gap-4">
                            {{-- Name --}}
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Name</label>
                                @if($product->proposed_name && $product->proposed_name !== $product->name)
                                    <p class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        {{ $product->proposed_name }}</p>
                                    <p class="text-[0.65rem] text-blue-600 mt-1 uppercase font-bold tracking-tight">* Slug will
                                        be updated on approval</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                            {{-- Tagline --}}
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline
                                    (Listing)</label>
                                @if($product->proposed_tagline && $product->proposed_tagline !== $product->tagline)
                                    <p class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        {{ $product->proposed_tagline }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                            {{-- Product Page Tagline --}}
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tagline (Product
                                    Page)</label>
                                @if($product->proposed_product_page_tagline && $product->proposed_product_page_tagline !== $product->product_page_tagline)
                                    <p class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        {{ $product->proposed_product_page_tagline }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Description --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Description</label>
                            @if($product->proposed_description && $product->proposed_description !== $product->description)
                                <div
                                    class="mt-1 text-sm text-gray-900 prose prose-sm max-w-none bg-yellow-100 p-3 rounded border border-yellow-200">
                                    {!! nl2br(e($product->proposed_description)) !!}
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                            @endif
                        </div>

                        {{-- Proposed Links --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Product Link</label>
                                @if($product->proposed_link && $product->proposed_link !== $product->link)
                                    <p
                                        class="text-sm text-blue-800 bg-yellow-100 p-2 rounded border border-yellow-200 break-all">
                                        {{ $product->proposed_link }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Video URL</label>
                                @if($product->proposed_video_url && $product->proposed_video_url !== $product->video_url)
                                    <p
                                        class="text-sm text-gray-900 bg-yellow-100 p-2 rounded border border-yellow-200 break-all">
                                        {{ $product->proposed_video_url }}</p>
                                @else
                                    <p class="text-sm text-gray-400 italic">(No change)</p>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Categories & Tech --}}
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Categories</label>
                                @php
                                    $currentCatIds = $product->categories->pluck('id')->sort()->values()->toArray();
                                    $proposedCatIds = $product->proposedCategories->pluck('id')->sort()->values()->toArray();
                                    $hasCatChanges = $currentCatIds !== $proposedCatIds;
                                @endphp

                                @if($hasCatChanges)
                                    <div class="mt-1 flex flex-wrap gap-1 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        @forelse($product->proposedCategories as $category)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-200 text-yellow-800 border border-yellow-300">{{ $category->name }}</span>
                                        @empty
                                            <span class="text-red-600 italic text-sm">Propose to remove all categories</span>
                                        @endforelse
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                                @endif
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tech Stacks</label>
                                @php
                                    $currentTechIds = $product->techStacks->pluck('id')->sort()->values()->toArray();
                                    $proposedTechIds = $product->proposedTechStacks->pluck('id')->sort()->values()->toArray();
                                    $hasTechChanges = $currentTechIds !== $proposedTechIds;
                                @endphp

                                @if($hasTechChanges)
                                    <div class="mt-1 flex flex-wrap gap-1 bg-yellow-100 p-2 rounded border border-yellow-200">
                                        @forelse($product->proposedTechStacks as $tech)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-200 text-yellow-800 border border-yellow-300">{{ $tech->name }}</span>
                                        @empty
                                            <span class="text-red-600 italic text-sm">Propose to remove all tech stacks</span>
                                        @endforelse
                                    </div>
                                @else
                                    <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                                @endif
                            </div>
                        </div>

                        {{-- Proposed Selling Info --}}
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Selling Status &
                                Price</label>
                            @php
                                $hasPriceChanges = !is_null($product->proposed_sell_product) && ($product->proposed_sell_product !== $product->sell_product || $product->proposed_asking_price != $product->asking_price);
                            @endphp
                            @if($hasPriceChanges)
                                <div class="mt-1 bg-yellow-100 p-2 rounded border border-yellow-200">
                                    <p class="text-sm text-gray-900 font-bold">
                                        {{ $product->proposed_sell_product ? 'For Sale' : 'Not for Sale' }}
                                        @if($product->proposed_sell_product && $product->proposed_asking_price)
                                            <span
                                                class="ml-2 font-black text-green-700">${{ number_format($product->proposed_asking_price, 2) }}</span>
                                        @endif
                                    </p>
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic mt-1">(No change)</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 p-6 border-t border-gray-200 bg-gray-50 flex items-center justify-end font-semibold gap-4">
                <form action="{{ route('admin.products.reject-edits', $product) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to reject these edits? All yellow-highlighted changes will be permanently discarded.');">
                    @csrf
                    <button type="submit"
                        class="px-6 py-2 border border-red-300 rounded-lg shadow-sm text-sm font-bold text-red-700 bg-white hover:bg-red-50 focus:outline-none transition-colors">
                        Reject All Proposed Edits
                    </button>
                </form>
                <form action="{{ route('admin.products.approve-edits', $product) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to approve these edits? The live product will be updated with all yellow-highlighted values.');">
                    @csrf
                    <button type="submit"
                        class="px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none transition-colors">
                        Approve & Apply All Edits
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection