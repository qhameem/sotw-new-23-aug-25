@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-center">
        <div class="w-full max-w-lg">
            <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <h1 class="text-2xl font-bold mb-4">Submit a Product for Review</h1>

                @if (session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('product-reviews.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="product_url">
                            Product URL <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('product_url') border-red-500 @enderror" id="product_url" name="product_url" type="url" placeholder="https://example.com" value="{{ old('product_url') }}" required>
                        @error('product_url')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="product_creator">
                            Product Creator <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('product_creator') border-red-500 @enderror" id="product_creator" name="product_creator" type="text" placeholder="John Doe or https://twitter.com/johndoe" value="{{ old('product_creator') }}" required>
                        <p class="text-gray-600 text-xs italic mt-2">Name or Social media profile URL of the product creator or the person paid for the review.</p>
                        @error('product_creator')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('email') border-red-500 @enderror" id="email" name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" required>
                        <p class="text-gray-600 text-xs italic mt-2">This is the correspondence email and can be used to contact with the product owner or the person paid for the review.</p>
                        @error('email')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="access_instructions">
                            Access Instructions
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('access_instructions') border-red-500 @enderror" id="access_instructions" name="access_instructions" rows="3" placeholder="e.g., Use user:user@example.com pass:password to login.">{{ old('access_instructions') }}</textarea>
                        <p class="text-gray-600 text-xs italic mt-2">This textarea will be used to enter give access to test the product, if needed.</p>
                        @error('access_instructions')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="other_instructions">
                            Other Instructions
                        </label>
                        <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('other_instructions') border-red-500 @enderror" id="other_instructions" name="other_instructions" rows="3" placeholder="Any other necessary instructions.">{{ old('other_instructions') }}</textarea>
                        <p class="text-gray-600 text-xs italic mt-2">This textarea will be used to enter any other necessary instruction the user feels are needed.</p>
                        @error('other_instructions')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection