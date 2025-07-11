@extends('layouts.app')

@section('title', 'Product Review - Software on the web')

@section('header-title')
    <h1 class="text-xl font-semibold text-gray-700 py-[1px]">
        Product to review
    </h1>
@endsection

@section('content')
<div class="container mx-auto">
    <div class="flex justify-center">
        <div class="w-full max-w-4xl">
            <div class="bg-white rounded px-8 pt-6 pb-8 mb-4">
                <h1 class="text-lg font-semibold mb-4 text-gray-700">Submit the product details you want us to write a review for</h1>
                <div class="h-4"></div>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('product-reviews.store') }}" method="POST">
                    @csrf
                    <div class="space-y-6 text-sm">
                        <!-- Product URL -->
                        <div class="grid md:grid-cols-4 gap-4 items-start">
                            <div class="md:col-span-1">
                                <label class="block font-semibold md:text-left md:pr-4" for="product_url">Product URL<span class="text-red-500 ml-1">*</span></label>
                            </div>
                            <div class="md:col-span-3">
                                <input type="url" id="product_url" name="product_url" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 placeholder-gray-400 focus:ring-primary-500 @error('product_url') border-red-500 @enderror" placeholder="https://example.com" value="{{ old('product_url') }}" required>
                                @error('product_url')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Product Creator -->
                        <div class="grid md:grid-cols-4 gap-4 items-start">
                            <div class="md:col-span-1">
                                <label class="block font-semibold md:text-left md:pr-4" for="product_creator">Product Creator<span class="text-red-500 ml-1">*</span></label>
                            </div>
                            <div class="md:col-span-3">
                                <input type="text" id="product_creator" name="product_creator" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500 placeholder-gray-400 @error('product_creator') border-red-500 @enderror" placeholder="John Doe or https://twitter.com/johndoe" value="{{ old('product_creator') }}" required>
                                <p class="text-xs text-gray-500 mt-1">Name or Social media profile URL of the product creator or the person paid for the review.</p>
                                @error('product_creator')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="grid md:grid-cols-4 gap-4 items-start">
                            <div class="md:col-span-1">
                                <label class="block font-semibold md:text-left md:pr-4" for="email">Email<span class="text-red-500 ml-1">*</span></label>
                            </div>
                            <div class="md:col-span-3">
                                <input type="email" id="email" name="email" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500 placeholder-gray-400 @error('email') border-red-500 @enderror" placeholder="you@example.com" value="{{ old('email') }}" required>
                                <p class="text-xs text-gray-500 mt-1">This is the correspondence email and can be used to contact with the product owner or the person paid for the review.</p>
                                @error('email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Access Instructions -->
                        <div class="grid md:grid-cols-4 gap-4 items-start">
                            <div class="md:col-span-1">
                                <label class="block font-semibold md:text-left md:pr-4" for="access_instructions">Access Instructions</label>
                            </div>
                            <div class="md:col-span-3">
                                <textarea id="access_instructions" name="access_instructions" rows="3" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500 placeholder-gray-400 @error('access_instructions') border-red-500 @enderror" placeholder="e.g., Use user:user@example.com pass:password to login.">{{ old('access_instructions') }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">This textarea will be used to enter give access to test the product, if needed.</p>
                                @error('access_instructions')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Other Instructions -->
                        <div class="grid md:grid-cols-4 gap-4 items-start">
                            <div class="md:col-span-1">
                                <label class="block font-semibold md:text-left md:pr-4" for="other_instructions">Other Instructions</label>
                            </div>
                            <div class="md:col-span-3">
                                <textarea id="other_instructions" name="other_instructions" rows="3" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:border-primary-500 focus:ring-primary-500 placeholder-gray-400 @error('other_instructions') border-red-500 @enderror" placeholder="Any other necessary instructions.">{{ old('other_instructions') }}</textarea>
                                <p class="text-xs text-gray-500 mt-1">This textarea will be used to enter any other necessary instruction the user feels are needed.</p>
                                @error('other_instructions')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="grid md:grid-cols-4 gap-4 mt-6">
                            <div class="md:col-start-2 md:col-span-3 flex justify-end items-center">
                                <x-primary-button type="submit">
                                    Submit
                                </x-primary-button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection