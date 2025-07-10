@extends('layouts.app')

@section('title', 'Legal | Software on the Web')

@section('content')
@section('header-title', 'Legal Information')

<div class="p-4">
    <div class="prose max-w-none">
        <!-- Content Index -->
        <div class="mb-10 p-6 bg-gray-100 rounded-md">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Content Index</h2>
            <ul class="list-disc list-inside space-y-2 text-base">
                <li><a href="#privacy-policy" class="text-blue-600 hover:underline">Privacy Policy</a></li>
                <li><a href="#cookie-policy" class="text-blue-600 hover:underline">Cookie Policy</a></li>
                <li><a href="#terms-of-use" class="text-blue-600 hover:underline">Terms of Use</a></li>
            </ul>
        </div>

        <!-- Privacy Policy Section -->
        <section class="mb-12">
            <h2 id="privacy-policy" class="text-2xl font-semibold text-gray-800 border-b pb-2 mb-6 scroll-mt-24">Privacy Policy</h2>
            <p class="text-sm text-gray-600 mb-4">Last updated: June 1, 2025</p>
            <p class="mb-4">At Software on the Web, accessible from https://softwareontheweb.com, we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and share your personal information.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Information We Collect</h3>
            <ul class="list-disc list-inside mb-4 space-y-1">
                <li>Your name and email address (if you subscribe or register)</li>
                <li>Usage data through analytics tools such as Google Analytics</li>
                <li>Payment information via third-party processors (e.g., Stripe or PayPal)</li>
            </ul>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">How We Use Your Information</h3>
            <ul class="list-disc list-inside mb-4 space-y-1">
                <li>To provide access to account-based features</li>
                <li>To send updates and newsletters (with your consent)</li>
                <li>To analyze website usage and improve our service</li>
                <li>To process payments for premium listings or services</li>
            </ul>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Third-Party Services</h3>
            <p class="mb-4">We use Google Analytics to track user activity, and Stripe or PayPal for secure payment processing. These services may use cookies and other technologies to collect data as described in their privacy policies.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Your Rights</h3>
            <p class="mb-4">You have the right to request access to, correction, or deletion of your personal data at any time. Contact us at <a href="mailto:hello@softwareontheweb.com" class="text-blue-600 hover:underline">hello@softwareontheweb.com</a>.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Contact Us</h3>
            <p class="mb-4">If you have any questions, email us at <a href="mailto:hello@softwareontheweb.com" class="text-blue-600 hover:underline">hello@softwareontheweb.com</a> or write to:</p>
            <p class="mb-4">Software on the Web<br>Canberra, ACT, Australia</p>
        </section>

        <!-- Cookie Policy Section -->
        <section class="mb-12">
            <h2 id="cookie-policy" class="text-3xl font-semibold text-gray-800 border-b pb-2 mb-6 scroll-mt-24">Cookie Policy</h2>
            <p class="text-sm text-gray-600 mb-4">Last updated: June 1, 2025</p>
            <p class="mb-4">This Cookie Policy explains how Software on the Web uses cookies and similar technologies when you visit our website at https://softwareontheweb.com.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">What Are Cookies?</h3>
            <p class="mb-4">Cookies are small text files stored on your device to help websites remember information about you.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">How We Use Cookies</h3>
            <ul class="list-disc list-inside mb-4 space-y-1">
                <li>To remember your preferences</li>
                <li>To analyze site traffic and usage (via Google Analytics)</li>
                <li>To provide secure payment processing (via Stripe or PayPal)</li>
                <li>To track affiliate/referral performance</li>
            </ul>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Third-Party Cookies</h3>
            <p class="mb-4">We use third-party services such as Google Analytics that may also place cookies on your device. You can learn more about how they use cookies in their respective privacy policies.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Managing Cookies</h3>
            <p class="mb-4">You can disable cookies via your browser settings. However, some features of the site may not work as intended without them.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Contact</h3>
            <p class="mb-4">Email: <a href="mailto:hello@softwareontheweb.com" class="text-blue-600 hover:underline">hello@softwareontheweb.com</a><br>
                Address: Canberra, ACT, Australia</p>
        </section>

        <!-- Terms of Use Section -->
        <section class="mb-12">
            <h2 id="terms-of-use" class="text-3xl font-semibold text-gray-800 border-b pb-2 mb-6 scroll-mt-24">Terms of Use</h2>
            <p class="text-sm text-gray-600 mb-4">Last updated: June 1, 2025</p>
            <p class="mb-4">By accessing or using https://softwareontheweb.com, you agree to be bound by these Terms of Use. If you do not agree, do not use our website.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">1. Use of Our Service</h3>
            <p class="mb-4">You may browse and use the site for discovering digital products. Account creation may be required for posting, saving, or subscribing to content.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">2. User Accounts</h3>
            <p class="mb-4">When creating an account, you agree to provide accurate and complete information. You are responsible for safeguarding your login credentials.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">3. Listings and Submissions</h3>
            <p class="mb-4">You may post or submit content (e.g. product listings) as long as it complies with applicable laws and our guidelines. We reserve the right to remove any content at our discretion.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">4. Payments</h3>
            <p class="mb-4">Paid services (e.g. featured listings) are handled by secure third-party processors. Refunds are subject to our refund policy.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">5. Affiliate Links & Ads</h3>
            <p class="mb-4">Some links or listings may contain affiliate links or sponsored content. We may earn a commission at no extra cost to you.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">6. Limitation of Liability</h3>
            <p class="mb-4">We are not liable for any damages or losses resulting from your use of the site or third-party services.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">7. Changes to Terms</h3>
            <p class="mb-4">We may update these terms from time to time. Continued use of the site means you accept the revised terms.</p>
            <h3 class="text-2xl font-semibold text-gray-700 mt-6 mb-3">Contact Us</h3>
            <p class="mb-4">Email: <a href="mailto:hello@softwareontheweb.com" class="text-blue-600 hover:underline">hello@softwareontheweb.com</a><br>
                Address: Canberra, ACT, Australia</p>
        </section>
    </div>
</div>
@endsection