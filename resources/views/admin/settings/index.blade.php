@extends('layouts.app') {{-- Or your admin layout if different --}}

@section('title', 'Admin Settings')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-800  mb-8 pt-16">Admin Settings</h1>

    @if(session('success'))
        <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg  " role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg  " role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white  shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 ">
                Database Export
            </h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500 ">
                <p>Export the entire application database as an SQL file. This file will contain all data, including user information, products, posts, etc.</p>
                <p class="mt-1 font-semibold text-red-600 ">Warning: The exported file contains sensitive data. Handle it securely and store it in a safe location.</p>
            </div>
            <div class="mt-5">
                <form action="{{ route('admin.settings.exportDatabase') }}" method="POST" onsubmit="return confirm('Are you sure you want to export the database? This may take a few moments.');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                        Export Database (SQL)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Premium Product Spots Section -->
    <div class="mt-10 bg-white  shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 ">
                Premium Product Spots
            </h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500 ">
                <p>Set the maximum number of premium product spots available for purchase.</p>
            </div>
            <form action="{{ route('admin.settings.storePremiumProductSpots') }}" method="POST" class="mt-5">
                @csrf
                <div>
                    <label for="premium_product_spots" class="block text-sm font-medium text-gray-700 ">
                        Number of Spots
                    </label>
                    <div class="mt-1">
                        <input type="number" id="premium_product_spots" name="premium_product_spots" min="0"
                                  class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                  value="{{ old('premium_product_spots', $premiumProductSpots ?? 6) }}">
                    </div>
                    @error('premium_product_spots')
                        <p class="mt-2 text-sm text-red-600 ">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                        Save Spots
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Google Analytics Integration Section -->
    <div class="mt-10 bg-white  shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 ">
                Google Analytics Integration
            </h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500 ">
                <p>Paste your full Google Analytics tracking code (usually starting with <code><script></code> and ending with <code></script></code>) below. You can find this code in your Google Analytics account under Admin > Data Streams > (select your stream) > Configure tag settings > Installation instructions.</p>
            </div>
            <form action="{{ route('admin.settings.storeAnalyticsCode') }}" method="POST" class="mt-5">
                @csrf
                <div>
                    <label for="google_analytics_code" class="block text-sm font-medium text-gray-700 ">
                        Google Analytics Tracking Code
                    </label>
                    <div class="mt-1">
                        <textarea id="google_analytics_code" name="google_analytics_code" rows="10"
                                  class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md    "
                                  placeholder="Paste your Google Analytics script here (e.g., <script async src=...></script>...)">{{ old('google_analytics_code', $googleAnalyticsCode ?? '') }}</textarea>
                    </div>
                    @error('google_analytics_code')
                        <p class="mt-2 text-sm text-red-600 ">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                        Save Analytics Code
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Email Sending Test Section -->
    <div class="mt-10 bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Test Email Sending
            </h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500">
                <p>Send a test email to verify your SMTP settings are configured correctly.</p>
            </div>
            <form id="test-email-form" action="{{ route('admin.settings.sendTestEmail') }}" method="POST" class="mt-5">
                @csrf
                <div class="flex items-center">
                    <input id="recipient-email" type="email" name="recipient_email" value="{{ auth()->user()->email }}" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md" required>
                    <button id="send-test-email-btn" type="submit" class="ml-3 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
                        Send Test Email
                    </button>
                </div>
            </form>
            <div id="email-log-container" class="mt-4" style="display: none;">
                <div class="bg-gray-900 text-white font-mono text-sm rounded-md p-4">
                    <p id="email-log" class="whitespace-pre-wrap"></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('test-email-form');
    var button = document.getElementById('send-test-email-btn');
    var logContainer = document.getElementById('email-log-container');
    var logElement = document.getElementById('email-log');

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            button.disabled = true;
            logContainer.style.display = 'block';
            logElement.textContent = 'Sending...';

            var formData = new FormData(form);
            var actionUrl = form.action;
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    if (!response.ok) {
                        var error = new Error(data.message || 'An unknown error occurred.');
                        throw error;
                    }
                    return data;
                });
            })
            .then(function(data) {
                logElement.textContent = data.message;
                button.disabled = false;
            })
            .catch(function(error) {
                logElement.textContent = error.message || 'An error occurred while sending the email. Check the browser console and server logs for more details.';
                button.disabled = false;
                console.error('Email send error:', error);
            });
        });
    }
});
</script>
@endpush