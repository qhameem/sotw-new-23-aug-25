@extends('layouts.app')

@section('title')
<h2 class="text-base font-semibold py-[3px] hidden md:block">Premium Spot</h2>
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
<h1 class="text-4xl font-bold text-gray-800">Premium Spot</h1>
<div class="h-2"></div>
<p class="text-lg text-gray-600">Feature your product in the most prominent advertising slot on our platform.</p>
<div class="h-8"></div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Maximum Visibility</h2>
        <ul class="space-y-4 text-gray-700">
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Top Placement:</span> Your ad will be displayed on every listing page, ensuring maximum exposure.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Direct Link:</span> A direct link to your website to drive traffic and conversions.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Stand Out:</span> A special design and badge to make your product stand out from the crowd.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">High Impressions:</span> Expect between 20,000 to 50,000 impressions.
                </div>
            </li>
        </ul>
    </div>
    <div class="border-2 border-sky-500 rounded-lg p-6 flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-2xl font-semibold text-gray-800">Book Your Spot</h3>
            <div class="bg-sky-50 border border-sky-300 rounded-md px-3 py-1 text-xs text-sky-500 font-bold">
                6 spots left
            </div>
        </div>
        <p class="text-gray-600 mb-4">Limited spots available per month.</p>
        <div class="text-5xl font-bold text-gray-800 mb-1">
            $149 <span class="opacity-40 font-normal text-sm">/month</span>
        </div>
        <div class="flex-grow"></div>
        <button class="bg-sky-500 hover:bg-sky-600 text-white px-8 py-3 rounded-md font-semibold w-full text-lg mt-6">
            Book a Premium Spot
        </button>
    </div>
</div>
@endsection