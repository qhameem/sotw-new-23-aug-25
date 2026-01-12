@extends('layouts.app')

@section('title')
Software Review
@endsection

@section('content')
<div class="mb-4">
    <a href="{{ route('promote') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-800 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Back to the advertising page
    </a>
</div>
<h1 class="text-4xl font-bold text-gray-800">Boost Your Product's Credibility with a Detailed Review</h1>
<div class="h-2"></div>
<p class="text-lg text-gray-600">We’ll write an honest, in-depth review of your product to build trust and drive sales.</p>
<div class="h-8"></div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">What a review does for you</h2>
        <ul class="space-y-4 text-gray-700">
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Rank on Google:</span> Our review will be optimized to rank for "[your_product] review" searches.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Gain Customer Confidence:</span> A thorough, unbiased review from a trusted source builds credibility.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Valuable Backlink:</span> Get a permanent backlink from a high-authority article.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Includes Fast-Track:</span> Your product submission is automatically fast-tracked at no extra cost.
                </div>
            </li>
        </ul>
    </div>
    <div class="border-2 border-gray-300 rounded-lg p-6 flex flex-col">
        <h3 class="text-2xl font-semibold text-gray-800 mb-2">Order a Review</h3>
        <p class="text-gray-600 mb-4">34 reviews already sold and published.</p>
        <div class="text-5xl font-bold text-gray-800 mb-6">
            $249
        </div>
        <div class="flex-grow"></div>
        <button class="bg-sky-500 hover:bg-sky-600 text-white px-8 py-3 rounded-md font-semibold w-full text-lg mt-6">
            Buy a Review
        </button>
    </div>
</div>
@endsection