@extends('layouts.app')

@section('content')
@section('title')
    <h1 class="text-xl font-semibold text-gray-800">Product Reviews</h1>
@endsection

@section('actions')
    {{-- No actions needed for this page --}}
@endsection

<div class="p-4">
    <div class="space-y-6">
        @forelse ($productReviews as $review)
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 truncate">
                    <a href="{{ $review->product_url }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                        {{ $review->product_url }}
                    </a>
                </h2>

                <div class="grid grid-cols-1 gap-y-4 mb-6">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="text-sm font-medium text-gray-500 w-28">Status</span>
                        @if($review->is_done)
                            <span class="text-sm text-white bg-green-500 px-2 py-1 rounded-full">Completed</span>
                        @else
                            <span class="text-sm text-white bg-yellow-500 px-2 py-1 rounded-full">Pending</span>
                        @endif
                    </div>

                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        <span class="text-sm font-medium text-gray-500 w-28">Submitted</span>
                        <span class="text-sm text-gray-800">{{ $review->created_at->format('F j, Y, g:i A') }}</span>
                    </div>

                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <span class="text-sm font-medium text-gray-500 w-28">Creator</span>
                        <span class="text-sm text-gray-800 truncate">{{ $review->product_creator }}</span>
                    </div>

                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <span class="text-sm font-medium text-gray-500 w-28">Contact Email</span>
                        <a href="mailto:{{ $review->email }}" class="text-sm text-blue-600 hover:underline truncate">{{ $review->email }}</a>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-md p-4">
                    <form action="{{ route('admin.product-reviews.update', $review) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-center mb-4">
                            <input type="checkbox" name="is_done" id="is_done_{{ $review->id }}" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $review->is_done ? 'checked' : '' }}>
                            <label for="is_done_{{ $review->id }}" class="ml-2 block text-sm text-gray-900">Mark as Done</label>
                        </div>
                        <div class="mb-4">
                            <label for="review_url_{{ $review->id }}" class="block text-sm font-medium text-gray-700">Review URL</label>
                            <input type="url" name="review_url" id="review_url_{{ $review->id }}" value="{{ $review->review_url }}" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="https://example.com/review">
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Update
                        </button>
                    </form>
                    <div class="border-t border-gray-200 my-4"></div>
                    @if($review->access_instructions)
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700">Access Instructions</h4>
                        <p class="text-sm text-gray-600 mt-1 whitespace-pre-wrap">{{ $review->access_instructions }}</p>
                    </div>
                    @endif
                    @if($review->other_instructions)
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-700">Other Instructions</h4>
                        <p class="text-sm text-gray-600 mt-1 whitespace-pre-wrap">{{ $review->other_instructions }}</p>
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-500">
                <p>No product reviews found.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $productReviews->links() }}
    </div>
</div>
@endsection