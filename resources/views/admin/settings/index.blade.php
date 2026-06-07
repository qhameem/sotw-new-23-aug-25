@extends('layouts.app', ['mainContentMaxWidth' => 'max-w-none', 'containerMaxWidth' => 'max-w-none', 'hideSidebar' => true]) {{-- Or your admin layout if different --}}

@section('title', 'Admin Settings')

@section('header-title')
    Admin Settings
@endsection

@section('content')
    <div class="w-full px-4 sm:px-6 lg:px-8 py-12">
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

        @php
            $adminSandboxEnabledInput = old('admin_add_product_sandbox_enabled', $adminSandboxEnabled ? '1' : '0');
        @endphp

        <div class="mt-10 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Add Product Sandbox Mode
                </h3>
                <div class="mt-2 max-w-2xl text-sm text-gray-500">
                    <p>Allow admins to use Sandbox mode on <code>/add-product</code> for dry-run submissions that never save to the database.</p>
                </div>
                <form action="{{ route('admin.settings.storeAdminSandboxMode') }}" method="POST" class="mt-5">
                    @csrf
                    <input type="hidden" name="admin_add_product_sandbox_enabled" value="0">
                    <label for="admin_add_product_sandbox_enabled" class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                        <input
                            id="admin_add_product_sandbox_enabled"
                            type="checkbox"
                            name="admin_add_product_sandbox_enabled"
                            value="1"
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            @checked((string) $adminSandboxEnabledInput === '1')
                        >
                        <div>
                            <p class="text-sm font-medium text-gray-900">Enable admin sandbox on the add-product page</p>
                            <p class="mt-1 text-sm text-gray-500">When off, the sandbox toggle is hidden and sandbox requests are rejected.</p>
                        </div>
                    </label>
                    @error('admin_add_product_sandbox_enabled')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                            Save Sandbox Setting
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-10 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Tool URL Settings
                </h3>
                <div class="mt-2 max-w-2xl text-sm text-gray-500">
                    <p>Keep the todo tool on the canonical <code>/tools/{tool-slug}</code> structure. Changing the slug here updates the live URL without another code change.</p>
                    <p class="mt-1">Current canonical URL: <code>{{ $todoListToolPath }}</code></p>
                </div>
                <form action="{{ route('admin.settings.storeToolSettings') }}" method="POST" class="mt-5">
                    @csrf
                    <div>
                        <label for="todo_list_tool_slug" class="block text-sm font-medium text-gray-700">
                            Todo Tool Slug
                        </label>
                        <div class="mt-1 flex items-center rounded-md shadow-sm">
                            <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">
                                /tools/
                            </span>
                            <input
                                type="text"
                                id="todo_list_tool_slug"
                                name="todo_list_tool_slug"
                                class="block w-full rounded-none rounded-r-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                value="{{ old('todo_list_tool_slug', $todoListToolSlug) }}"
                                inputmode="url"
                                pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                placeholder="todo-list-app"
                                required
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">Use lowercase letters, numbers, and hyphens only.</p>
                        @error('todo_list_tool_slug')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                            Save Tool URL
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Header Code Injection Section -->
        <div class="mt-10 bg-white  shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 ">
                    Header Code Injection
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500 ">
                    <p>Paste any code here that needs to be injected into the site <code>&lt;head&gt;</code> section.</p>
                    <p class="mt-1">Use this for Google Analytics, Google Tag Manager, verification tags, chat widgets, or any other service that asks you to add code inside <code>&lt;head&gt;</code>.</p>
                </div>
                <form action="{{ route('admin.settings.storeAnalyticsCode') }}" method="POST" class="mt-5">
                    @csrf
                    <div class="mb-4 rounded-lg border {{ $hasHeaderCodeInjection ? 'border-green-200 bg-green-50 text-green-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} px-4 py-3 text-sm">
                        @if($hasHeaderCodeInjection)
                            Header code injection is active. Your saved code will be injected into the <code>&lt;head&gt;</code> of public pages.
                        @else
                            No header code injection is active right now.
                        @endif
                    </div>
                    <div>
                        <label for="google_analytics_code" class="block text-sm font-medium text-gray-700 ">
                            Header Code
                        </label>
                        <div class="mt-1">
                            <textarea id="google_analytics_code" name="google_analytics_code" rows="10"
                                class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md    "
                                placeholder="Paste your head code here (e.g., <script async src=...></script> or a verification meta tag)">{{ old('google_analytics_code', $googleAnalyticsCode ?? '') }}</textarea>
                        </div>
                        @error('google_analytics_code')
                            <p class="mt-2 text-sm text-red-600 ">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 ">
                            Save Header Code
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

        <div class="mt-10 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            AI Provider Status
                        </h3>
                        <div class="mt-2 max-w-2xl text-sm text-gray-500">
                            <p>Check live AI provider availability, known quota signals, and reset windows.</p>
                            <p class="mt-1">Times below are shown in your browser's local time when available.</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-2 sm:items-end">
                        <button
                            type="button"
                            id="refresh-ai-provider-status"
                            class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            Check AI quota now
                        </button>
                        <p id="ai-provider-status-timezone" class="text-xs text-gray-500"></p>
                    </div>
                </div>

                <div id="ai-provider-status-feedback" class="mt-4 hidden rounded-md border px-4 py-3 text-sm"></div>
                <div id="ai-provider-status-list" class="mt-5 grid gap-4 lg:grid-cols-2"></div>
            </div>
        </div>

        <div class="mt-10 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Outbound Link Policy
                </h3>
                <div class="mt-2 max-w-2xl text-sm text-gray-500">
                    <p>All outbound links now default to nofollow. Use the rules page to create dofollow exceptions, and the discovered links page to find URLs from articles, products, ads, and embeds in one place.</p>
                </div>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('admin.outbound-links.rules.index') }}"
                        class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        Manage Rules
                    </a>
                    <a href="{{ route('admin.outbound-links.occurrences.index') }}"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Browse Discovered Links
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
                    <p class="mt-1">Outbound links inside the embed code will still follow the outbound link policy, so only paste embed code from sources you trust.</p>
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
                        <div class="mt-3 space-y-1 text-xs text-gray-500">
                            @if(!empty($badgeImageSvgUrl))
                                <p>SVG: <a href="{{ $badgeImageSvgUrl }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-700">{{ $badgeImageSvgUrl }}</a></p>
                            @endif
                            @if(!empty($badgeImagePngUrl))
                                <p>PNG: <a href="{{ $badgeImagePngUrl }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-700">{{ $badgeImagePngUrl }}</a></p>
                            @endif
                            @if(!empty($badgeImageWebpUrl))
                                <p>WEBP: <a href="{{ $badgeImageWebpUrl }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-700">{{ $badgeImageWebpUrl }}</a></p>
                            @endif
                        </div>
                    </div>
                @endif
                <form action="{{ route('admin.settings.storeBadgeEmbedCode') }}" method="POST" class="mt-5">
                    @csrf
                    <div>
                        <label for="badge_embed_code" class="block text-sm font-medium text-gray-700">
                            Badge Share Code
                        </label>
                        <p class="mt-2 text-sm text-gray-500">
                            This is the code other people will copy and paste on their sites to share the badge.
                            Leave it empty any time you want us to fall back to the default generated badge code.
                        </p>
                        <div class="mt-3">
                            <textarea id="badge_embed_code" name="badge_embed_code" rows="8"
                                class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                placeholder="<a href=&quot;...&quot; rel=&quot;dofollow&quot;>...">{{ old('badge_embed_code', $badgeEmbedCode ?? '') }}</textarea>
                        </div>
                        @error('badge_embed_code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Save Badge Code
                        </button>
                    </div>
                </form>
                <form action="{{ route('admin.settings.storeBadgeImage') }}" method="POST" enctype="multipart/form-data"
                    class="mt-5">
                    @csrf
                    <div class="space-y-5">
                        <div>
                            <label for="badge_image_svg" class="block text-sm font-medium text-gray-700">
                                Upload SVG Badge
                            </label>
                            <p class="mt-2 text-sm text-gray-500">
                                Recommended as the primary format because it stays sharp at any size.
                            </p>
                            <div class="mt-2">
                                <input type="file" id="badge_image_svg" name="badge_image_svg"
                                    accept=".svg,image/svg+xml"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            </div>
                            @error('badge_image_svg')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="badge_image_png" class="block text-sm font-medium text-gray-700">
                                Upload PNG Fallback
                            </label>
                            <p class="mt-2 text-sm text-gray-500">
                                Optional, but recommended so the default share code has a reliable fallback and the old <code>/images/badge.webp</code> badge can be regenerated automatically.
                            </p>
                            <div class="mt-2">
                                <input type="file" id="badge_image_png" name="badge_image_png"
                                    accept=".png,image/png"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            </div>
                            @error('badge_image_png')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                            Upload either one by itself, or upload both together. When both are available, the default badge code will use the SVG first and the PNG as fallback. Any time you upload a new PNG badge, we will also regenerate <code>/images/badge.webp</code> for older sites already using that badge URL.
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Save Badge Assets
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
            var aiProviderStatusButton = document.getElementById('refresh-ai-provider-status');
            var aiProviderStatusList = document.getElementById('ai-provider-status-list');
            var aiProviderStatusFeedback = document.getElementById('ai-provider-status-feedback');
            var aiProviderStatusTimezone = document.getElementById('ai-provider-status-timezone');
            var initialAiProviderStatus = @json($aiProviderStatusSnapshots ?? []);
            var aiProviderStatusUrl = @json(route('admin.settings.aiProviderStatus'));
            var aiProviderToggleUrl = @json(route('admin.settings.storeAiProviderEnabled'));
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatLocalDateTime(value) {
                if (!value) {
                    return '—';
                }

                var date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return '—';
                }

                return new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'medium',
                    timeStyle: 'short'
                }).format(date);
            }

            function stateClasses(state) {
                if (state === 'ok') {
                    return 'bg-green-100 text-green-800';
                }

                if (state === 'limited') {
                    return 'bg-amber-100 text-amber-800';
                }

                if (state === 'missing_key') {
                    return 'bg-gray-100 text-gray-700';
                }

                if (state === 'error') {
                    return 'bg-red-100 text-red-800';
                }

                return 'bg-blue-100 text-blue-800';
            }

            function enabledClasses(enabled) {
                return enabled
                    ? 'bg-emerald-100 text-emerald-800'
                    : 'bg-slate-100 text-slate-700';
            }

            function renderAiProviderStatus(providers) {
                if (!aiProviderStatusList) {
                    return;
                }

                if (!Array.isArray(providers) || providers.length === 0) {
                    aiProviderStatusList.innerHTML = '<div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">No AI provider status data is available yet.</div>';
                    return;
                }

                aiProviderStatusList.innerHTML = providers.map(function (provider) {
                    var notes = Array.isArray(provider.notes) && provider.notes.length > 0
                        ? '<ul class="mt-3 space-y-1 text-xs text-gray-500">' + provider.notes.map(function (note) {
                            return '<li>' + escapeHtml(note) + '</li>';
                        }).join('') + '</ul>'
                        : '';

                    var rows = [
                        ['Model', provider.model || '—'],
                        ['Enabled', provider.enabled ? 'Yes' : 'No'],
                        ['API key configured', provider.configured ? 'Yes' : 'No'],
                        ['Last checked', formatLocalDateTime(provider.checked_at)],
                        ['Retry / unblock hint', formatLocalDateTime(provider.retry_at)],
                        ['Request limit', provider.request_limit != null ? provider.request_limit : '—'],
                        ['Requests remaining', provider.request_remaining != null ? provider.request_remaining : '—'],
                        ['Request reset', formatLocalDateTime(provider.request_reset_at)],
                        ['Token limit', provider.token_limit != null ? provider.token_limit : '—'],
                        ['Tokens remaining', provider.token_remaining != null ? provider.token_remaining : '—'],
                        ['Token reset', formatLocalDateTime(provider.token_reset_at)],
                        ['Credit limit', provider.credit_limit != null ? provider.credit_limit : '—'],
                        ['Credits remaining', provider.credit_remaining != null ? provider.credit_remaining : '—'],
                        ['Limit reset type', provider.limit_reset_type || '—'],
                        ['Next limit reset', formatLocalDateTime(provider.next_limit_reset_at)],
                        ['Usage total', provider.usage_total != null ? provider.usage_total : '—'],
                        ['Usage today', provider.usage_daily != null ? provider.usage_daily : '—'],
                        ['Usage this week', provider.usage_weekly != null ? provider.usage_weekly : '—'],
                        ['Usage this month', provider.usage_monthly != null ? provider.usage_monthly : '—'],
                        ['Daily reset', formatLocalDateTime(provider.daily_reset_at)],
                    ].map(function (row) {
                        return '<div class="flex items-start justify-between gap-4 py-2"><dt class="text-sm text-gray-500">' + escapeHtml(row[0]) + '</dt><dd class="text-sm font-medium text-gray-900 text-right">' + escapeHtml(row[1]) + '</dd></div>';
                    }).join('');

                    var toggleLabel = provider.enabled ? 'Disable API' : 'Enable API';
                    var toggleClasses = provider.enabled
                        ? 'border-red-200 text-red-700 hover:border-red-300 hover:bg-red-50'
                        : 'border-emerald-200 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-50';

                    return ''
                        + '<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">'
                        + '  <div class="flex items-start justify-between gap-3">'
                        + '    <div>'
                        + '      <h4 class="text-base font-semibold text-gray-900">' + escapeHtml(provider.label || 'Provider') + '</h4>'
                        + '      <p class="mt-1 text-sm text-gray-500">' + escapeHtml(provider.message || '') + '</p>'
                        + '    </div>'
                        + '    <div class="flex flex-col items-end gap-2">'
                        + '      <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' + stateClasses(provider.state) + '">' + escapeHtml(provider.status_label || 'Unknown') + '</span>'
                        + '      <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' + enabledClasses(!!provider.enabled) + '">' + (provider.enabled ? 'Enabled' : 'Disabled') + '</span>'
                        + '    </div>'
                        + '  </div>'
                        + '  <dl class="mt-4 divide-y divide-gray-100">' + rows + '</dl>'
                        + notes
                        + '  <div class="mt-4 flex flex-wrap gap-3 text-sm">'
                        + '    <button type="button" data-ai-provider-toggle="' + escapeHtml(provider.provider || '') + '" data-ai-provider-enabled="' + (provider.enabled ? '0' : '1') + '" class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-medium transition-colors ' + toggleClasses + '">' + toggleLabel + '</button>'
                        + '    <a href="' + escapeHtml(provider.dashboard_url || '#') + '" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-700 hover:text-primary-800">Open dashboard</a>'
                        + '    <a href="' + escapeHtml(provider.docs_url || '#') + '" target="_blank" rel="noopener noreferrer" class="font-medium text-gray-600 hover:text-gray-800">Docs</a>'
                        + '  </div>'
                        + '</div>';
                }).join('');
            }

            function setAiProviderFeedback(message, tone) {
                if (!aiProviderStatusFeedback) {
                    return;
                }

                if (!message) {
                    aiProviderStatusFeedback.className = 'mt-4 hidden rounded-md border px-4 py-3 text-sm';
                    aiProviderStatusFeedback.textContent = '';
                    return;
                }

                var toneClasses = {
                    success: 'mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800',
                    error: 'mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800',
                    info: 'mt-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800'
                };

                aiProviderStatusFeedback.className = toneClasses[tone] || toneClasses.info;
                aiProviderStatusFeedback.textContent = message;
            }

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

            if (aiProviderStatusTimezone) {
                var browserTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                aiProviderStatusTimezone.textContent = browserTimeZone
                    ? 'Browser time zone: ' + browserTimeZone
                    : 'Browser local time';
            }

            renderAiProviderStatus(initialAiProviderStatus);

            if (aiProviderStatusButton) {
                aiProviderStatusButton.addEventListener('click', function () {
                    aiProviderStatusButton.disabled = true;
                    aiProviderStatusButton.textContent = 'Checking...';
                    setAiProviderFeedback('Running live provider checks...', 'info');

                    fetch(aiProviderStatusUrl + '?refresh=1', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Failed to refresh AI provider status.');
                                }

                                return data;
                            });
                        })
                        .then(function (data) {
                            renderAiProviderStatus(data.providers || []);
                            setAiProviderFeedback('AI provider status was refreshed successfully.', 'success');
                        })
                        .catch(function (error) {
                            console.error('AI provider status refresh error:', error);
                            setAiProviderFeedback(error.message || 'Failed to refresh AI provider status.', 'error');
                        })
                        .finally(function () {
                            aiProviderStatusButton.disabled = false;
                            aiProviderStatusButton.textContent = 'Check AI quota now';
                        });
                });
            }

            if (aiProviderStatusList) {
                aiProviderStatusList.addEventListener('click', function (event) {
                    var toggleButton = event.target.closest('[data-ai-provider-toggle]');

                    if (!toggleButton) {
                        return;
                    }

                    var provider = toggleButton.getAttribute('data-ai-provider-toggle');
                    var enabled = toggleButton.getAttribute('data-ai-provider-enabled') === '1';

                    toggleButton.disabled = true;
                    setAiProviderFeedback('Saving AI provider setting...', 'info');

                    fetch(aiProviderToggleUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            provider: provider,
                            enabled: enabled
                        })
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Failed to save AI provider setting.');
                                }

                                return data;
                            });
                        })
                        .then(function (data) {
                            renderAiProviderStatus(data.providers || []);
                            setAiProviderFeedback(data.message || 'AI provider setting saved successfully.', 'success');
                        })
                        .catch(function (error) {
                            console.error('AI provider toggle error:', error);
                            setAiProviderFeedback(error.message || 'Failed to save AI provider setting.', 'error');
                        });
                });
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
