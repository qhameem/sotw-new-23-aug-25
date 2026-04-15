@extends('layouts.app')

@section('title', 'Product Submission Complete')

@section('header-title')
    <h1 class="sm:text-sm md:text-base font-bold text-gray-800 py-[3px]">Product Submission Complete</h1>
@endsection

@section('actions')
@endsection

@section('content')
    @php
        $referenceId = 'SOTW-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
        $submittedAt = $product->created_at?->timezone(config('app.timezone'))->format('M j, Y \\a\\t g:i A');
    @endphp
    <div class="p-4 mx-auto max-w-7xl">
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Submission Received</h2>
            <p class="text-sm text-gray-700 mb-4">
                Your product has been received. Keep this confirmation for your records.
            </p>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div class="rounded-md bg-gray-50 border border-gray-100 px-3 py-2">
                    <dt class="text-gray-500">Reference ID</dt>
                    <dd class="font-medium text-gray-900">{{ $referenceId }}</dd>
                </div>
                <div class="rounded-md bg-gray-50 border border-gray-100 px-3 py-2">
                    <dt class="text-gray-500">Submitted</dt>
                    <dd class="font-medium text-gray-900">{{ $submittedAt ?? 'Just now' }}</dd>
                </div>
            </dl>
        </div>

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

            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">What Happens Next</h3>
                <ol class="space-y-2 text-sm text-gray-700 list-decimal list-inside">
                    <li>Your placement is already approved.</li>
                    <li>Add the badge snippet to your website so verification can pass before launch day.</li>
                    <li>We run automatic badge checks before launch.</li>
                    <li>Your listing goes live on <strong>{{ $launchDateFormatted }}</strong>.</li>
                </ol>
            </div>

            {{-- Badge Snippet Section --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Place This Badge On Your Site</h3>
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
                        <strong>Tip:</strong> Place this in your site's footer, homepage, or an "As Seen On" section.
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

            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">What Happens Next</h3>
                <ol class="space-y-2 text-sm text-gray-700 list-decimal list-inside">
                    <li>Your listing enters our review queue now.</li>
                    <li>Typical review time is up to <strong>{{ $daysToLive }} days</strong>.</li>
                    <li>You can edit this submission while it's pending.</li>
                    <li>We will notify you once it is approved and scheduled.</li>
                </ol>
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Track And Manage</h3>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('products.my') }}"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 transition">
                    Track My Submissions
                </a>
                <a href="{{ route('products.edit', $product) }}"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition">
                    Edit This Submission
                </a>
            </div>
            <p class="mt-4 text-xs text-gray-500">
                Need help? Include reference <strong>{{ $referenceId }}</strong> when contacting support.
            </p>
        </div>

    </div>
    {{-- Mobile view for quick actions --}}
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
        <h3 class="font-noto-serif text-lg font-medium tracking-tighter italic text-gray-800 mb-4">Quick Actions</h3>
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
