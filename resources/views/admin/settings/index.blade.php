@extends('layouts.app') {{-- Or your admin layout if different --}}

@section('title', 'Admin Settings')

@section('header-title')
    Admin Settings
@endsection

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
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
                    <p>Export the entire application database as an SQL file. This file will contain all data, including
                        user information, products, posts, etc.</p>
                    <p class="mt-1 font-semibold text-red-600 ">Warning: The exported file contains sensitive data. Handle
                        it securely and store it in a safe location.</p>
                </div>
                <div class="mt-5">
                    <form action="{{ route('admin.settings.exportDatabase') }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to export the database? This may take a few moments.');">
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

        <!-- Product Publishing Time Section -->
        <div class="mt-10 bg-white  shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 ">
                    Product Publishing Time
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500 ">
                    <p>Set the default time (in UTC) for scheduled products to be published.</p>
                </div>
                <form action="{{ route('admin.settings.storePublishTime') }}" method="POST" class="mt-5">
                    @csrf
                    <div>
                        <label for="product_publish_time" class="block text-sm font-medium text-gray-700 ">
                            Publish Time (UTC)
                        </label>
                        <div class="mt-1">
                            <input type="time" id="product_publish_time" name="product_publish_time"
                                class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                value="{{ old('product_publish_time', $productPublishTime ?? '07:00') }}">
                        </div>
                        @error('product_publish_time')
                            <p class="mt-2 text-sm text-red-600 ">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                            Save Time
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
                    <p>Paste your full Google Analytics tracking code (usually starting with <code><script></code > and
                        ending with <code></script></code>) below. You can find this code in your Google Analytics account
                        under Admin > Data Streams > (select your stream) > Configure tag settings > Installation
                        instructions.</p>
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
                        <input id="recipient-email" type="email" name="recipient_email" value="{{ auth()->user()->email }}"
                            class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md"
                            required>
                        <button id="send-test-email-btn" type="submit"
                            class="ml-3 inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50">
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




        <!-- Email Templates Section -->
        <div class="mt-10 bg-white  shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 ">
                    Email Templates
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500 ">
                    <p>Customize the emails sent from the application.</p>
                </div>
                <div class="mt-5">
                    <a href="{{ route('admin.settings.emailTemplates') }}"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                        Manage Email Templates
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-10 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Screenshot Provider Debug
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                    <p>Inspect weighted provider rotation, local usage counters, remaining quota, and reset windows.</p>
                </div>
                <div class="mt-5">
                    <a href="{{ route('admin.settings.screenshotProviders') }}"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                        Open Screenshot Debug
                    </a>
                </div>
            </div>
        </div>

        @php
            $footerBadgeEmbedCodesInput = old('footer_badge_embed_codes', $footerBadgeEmbedCodes ?? []);

            if (!is_array($footerBadgeEmbedCodesInput)) {
                $footerBadgeEmbedCodesInput = [$footerBadgeEmbedCodesInput];
            }

            $footerBadgeEmbedCodesInput = array_values($footerBadgeEmbedCodesInput);

            if (count(array_filter($footerBadgeEmbedCodesInput, fn ($code) => filled(trim((string) $code)))) === 0) {
                $footerBadgeEmbedCodesInput = [''];
            }
        @endphp

        <div class="mt-10 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Footer Badge Embed Codes
                </h3>
                <div class="mt-2 max-w-2xl text-sm text-gray-500">
                    <p>Add one or more raw HTML embed codes to the public footer for trust badges or similar widgets.</p>
                    <p class="mt-1">Each code is rendered as-is, so only paste embed code from sources you trust.</p>
                </div>
                <form action="{{ route('admin.settings.storeFooterEmbedCodes') }}" method="POST" class="mt-5">
                    @csrf

                    <div id="footer-embed-code-list" class="space-y-4">
                        @foreach ($footerBadgeEmbedCodesInput as $index => $footerBadgeEmbedCode)
                            <div class="rounded-lg border border-gray-200 p-4 footer-embed-code-item">
                                <div class="flex items-center justify-between gap-4">
                                    <label for="footer_badge_embed_codes_{{ $index }}" class="block text-sm font-medium text-gray-700">
                                        Embed Code {{ $loop->iteration }}
                                    </label>
                                    <button type="button"
                                        class="text-sm font-medium text-red-600 hover:text-red-700"
                                        data-remove-footer-embed-code>
                                        Remove
                                    </button>
                                </div>
                                <div class="mt-3">
                                    <textarea id="footer_badge_embed_codes_{{ $index }}" name="footer_badge_embed_codes[]" rows="5"
                                        class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                        placeholder="<a href=&quot;...&quot;><img src=&quot;...&quot; alt=&quot;Badge&quot;></a>">{{ $footerBadgeEmbedCode }}</textarea>
                                </div>
                                @error("footer_badge_embed_codes.$index")
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    @error('footer_badge_embed_codes')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <template id="footer-embed-code-template">
                        <div class="rounded-lg border border-gray-200 p-4 footer-embed-code-item">
                            <div class="flex items-center justify-between gap-4">
                                <label class="block text-sm font-medium text-gray-700" data-footer-embed-code-label>
                                    Embed Code
                                </label>
                                <button type="button"
                                    class="text-sm font-medium text-red-600 hover:text-red-700"
                                    data-remove-footer-embed-code>
                                    Remove
                                </button>
                            </div>
                            <div class="mt-3">
                                <textarea name="footer_badge_embed_codes[]" rows="5"
                                    class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                    placeholder="<a href=&quot;...&quot;><img src=&quot;...&quot; alt=&quot;Badge&quot;></a>"></textarea>
                            </div>
                        </div>
                    </template>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button type="button" id="add-footer-embed-code"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Add Another Embed Code
                        </button>
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Save Footer Embed Codes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Badge Image Management Section -->
        <div class="mt-10 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Badge Image
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                    <p>Upload the badge image that users place on their sites for badge-based submissions. This image is
                        used in the HTML snippet provided to users.</p>
                </div>
                @if(!empty($badgeImageUrl))
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Current Badge:</p>
                        <img src="{{ $badgeImageUrl }}" alt="Current badge" class="max-h-20 border border-gray-200 rounded p-1">
                    </div>
                @endif
                <form action="{{ route('admin.settings.storeBadgeImage') }}" method="POST" enctype="multipart/form-data"
                    class="mt-5">
                    @csrf
                    <div>
                        <label for="badge_image" class="block text-sm font-medium text-gray-700">
                            Upload New Badge Image
                        </label>
                        <div class="mt-1">
                            <input type="file" id="badge_image" name="badge_image"
                                accept="image/png,image/svg+xml,image/jpeg,image/webp"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                                required>
                        </div>
                        @error('badge_image')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Upload Badge
                        </button>
                    </div>
                </form>
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
            var footerEmbedCodeList = document.getElementById('footer-embed-code-list');
            var footerEmbedCodeTemplate = document.getElementById('footer-embed-code-template');
            var addFooterEmbedCodeButton = document.getElementById('add-footer-embed-code');

            function updateFooterEmbedCodeLabels() {
                if (!footerEmbedCodeList) {
                    return;
                }

                var items = footerEmbedCodeList.querySelectorAll('.footer-embed-code-item');

                items.forEach(function (item, index) {
                    var label = item.querySelector('label');
                    var textarea = item.querySelector('textarea');

                    if (label) {
                        label.textContent = 'Embed Code ' + (index + 1);
                        label.setAttribute('for', 'footer_badge_embed_codes_' + index);
                    }

                    if (textarea) {
                        textarea.id = 'footer_badge_embed_codes_' + index;
                    }
                });
            }

            if (addFooterEmbedCodeButton && footerEmbedCodeList && footerEmbedCodeTemplate) {
                addFooterEmbedCodeButton.addEventListener('click', function () {
                    var templateContent = footerEmbedCodeTemplate.content.cloneNode(true);
                    footerEmbedCodeList.appendChild(templateContent);
                    updateFooterEmbedCodeLabels();
                });

                footerEmbedCodeList.addEventListener('click', function (event) {
                    if (!event.target.matches('[data-remove-footer-embed-code]')) {
                        return;
                    }

                    var item = event.target.closest('.footer-embed-code-item');

                    if (!item) {
                        return;
                    }

                    var items = footerEmbedCodeList.querySelectorAll('.footer-embed-code-item');

                    if (items.length === 1) {
                        var textarea = item.querySelector('textarea');
                        if (textarea) {
                            textarea.value = '';
                        }
                        return;
                    }

                    item.remove();
                    updateFooterEmbedCodeLabels();
                });

                updateFooterEmbedCodeLabels();
            }

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
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    var error = new Error(data.message || 'An unknown error occurred.');
                                    throw error;
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            logElement.textContent = data.message;
                            button.disabled = false;
                        })
                        .catch(function (error) {
                            logElement.textContent = error.message || 'An error occurred while sending the email. Check the browser console and server logs for more details.';
                            button.disabled = false;
                            console.error('Email send error:', error);
                        });
                });
            }
        });
    </script>
@endpush
