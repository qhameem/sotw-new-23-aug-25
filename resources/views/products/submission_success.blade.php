@extends('layouts.app')

@section('title', 'Product Submission Complete')

@section('header-title')
    <h1 class="sm:text-sm md:text-base font-bold text-gray-800 py-[3px]">Product Submission Complete</h1>
@endsection

@section('actions')
@endsection

@section('content')
    <div class="p-4 mx-auto max-w-7xl">

        @if($product->submission_type === 'badge')
            {{-- Badge Submission Success --}}
            <div class="flex items-center p-4 mb-6 text-sm text-emerald-800 border border-emerald-300 rounded-lg bg-emerald-50"
                role="alert">
                <div class="px-3">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <span class="font-semibold">Your product is approved!</span>
                    Launching on <strong>{{ $launchDateFormatted }}</strong>.
                </div>
            </div>

            {{-- Badge Snippet Section --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">📋 Place this badge on your site</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Copy the HTML snippet below and paste it anywhere on your website. We'll verify it before your launch day.
                    This gives you a guaranteed <strong>dofollow backlink</strong>.
                </p>

                <div class="relative">
                    <pre id="badge-snippet"
                        class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-xs font-mono text-gray-700 whitespace-pre-wrap break-all overflow-x-auto">{{ $badgeSnippet }}</pre>
                    <button
                        onclick="navigator.clipboard.writeText(document.getElementById('badge-snippet').textContent).then(() => { this.textContent = 'Copied!'; setTimeout(() => { this.textContent = 'Copy'; }, 2000); })"
                        class="absolute top-2 right-2 px-3 py-1 text-xs font-medium bg-white border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700 transition">
                        Copy
                    </button>
                </div>

                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>💡 Tip:</strong> Place this in your site's footer, homepage, or an "As Seen On" section.
                        We'll check for the badge before your Monday launch.
                    </p>
                </div>
            </div>
        @else
            {{-- Free Submission Success --}}
            <div class="flex items-center p-4 mb-6 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50"
                role="alert">
                <div class="px-3"><x-simpleline-check class="w-4 h-4" /></div>
                <div>
                    <span class="font-semibold">Thank you! Your product has been submitted for review.</span>
                </div>
            </div>
        @endif

        <h3 class="mb-5 font-noto-serif text-lg font-medium text-gray-800 italic">What's next?</h3>

        <div class="space-y-6">

            <x-promote.fast-track-card />
            <div class="flex flex-col gap-2">
                <div>
                    <x-promote.premium-spot-card :spots-available="$spotsAvailable" />
                </div>
                <div>
                    <x-promote.product-review-card />
                </div>

            </div>





        </div>
    </div>
    {{-- Mobile view for "What's next?" --}}
    <div class="md:hidden mt-8">

        <div class="space-y-3">
            <a href="{{ route('products.my') }}"
                class="block text-center w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150 py-2">
                Show all my submissions
            </a>
            @if(isset($product))
                <a href="{{ route('products.edit', $product) }}"
                    class="block text-center w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150 py-2">
                    Edit this submission
                </a>
            @endif
        </div>
    </div>
@endsection

@section('right_sidebar_content')
    <div class="p-6 items-start hidden md:block">
        <h3 class="font-noto-serif text-lg font-medium tracking-tighter italic text-gray-800 mb-4">Other options</h3>
        <div class="space-y-2">

            <a href="{{ route('products.my') }}"
                class="block w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150 py-2">
                Show all my submissions
            </a>
            @if(isset($product))
                <a href="{{ route('products.edit', $product) }}"
                    class="block w-full underline underline-offset-2 text-sm text-blue-600 hover:text-primary-500 transition ease-in-out duration-150">
                    Edit this submission
                </a>
            @endif
        </div>
    </div>
@endsection