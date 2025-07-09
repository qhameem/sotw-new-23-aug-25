@extends('layouts.app')

@section('title')
<h2 class="text-base font-semibold py-[3px] hidden md:block">Fast-track Submission</h2>
@endsection

@section('content')
<div class="mb-4">
    <a href="{{ route('promote') }}" class="text-xs font-medium text-gray-600 items-center inline-flex border border-sky-200 px-2 py-0.5 rounded-md hover:bg-sky-50">
        &larr;
        Back to the advertising page
    </a>
</div>
<h1 class="text-4xl font-bold text-gray-800">Skip the Waiting Line</h1>
<div class="h-2"></div>
<p class="text-lg text-gray-600">Get your product in front of thousands of potential customers without the wait. Fast-track your submission and get published on the day you choose.</p>
<div class="h-8"></div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">What you get</h2>
        <ul class="space-y-4 text-gray-700">
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Guaranteed Publication:</span> Your product will be published on your chosen day. No more waiting in a queue.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Permanent Listing & Backlink:</span> Your product stays on our site forever, providing a valuable, permanent backlink.
                </div>
            </li>
            <li class="flex items-start gap-3">
                <div class="text-sky-500 text-xl font-extrabold mt-1">✓</div>
                <div>
                    <span class="font-semibold">Social Media Exposure:</span> If you're one of the first three launches of the day, we'll feature you on our social media channels.
                </div>
            </li>
        </ul>
    </div>
    <div class="bg-gradient-to-tr from-white to-sky-50 border border-sky-200 rounded-xl p-6">
        <h3 class="text-2xl font-semibold text-gray-800 mb-2">One-time Payment</h3>
        <p class="text-gray-600 mb-4">All the benefits for a single payment.</p>
        <div class="text-5xl font-extrabold text-gray-800 mb-6">
            $30
        </div>
        <button @click="promoteModalOpen = true" class="bg-sky-500 hover:bg-sky-600 text-white px-8 py-3 rounded-md font-semibold w-full text-lg">
            Skip the Line Now
        </button>
    </div>
</div>
@endsection