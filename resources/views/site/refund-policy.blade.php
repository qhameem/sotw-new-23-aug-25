@extends('layouts.app')

@section('title', 'Refund & Cancellation Policy | Software on the Web')
@section('description', 'Refund and cancellation terms for paid product listings on Software on the Web.')
@section('header-title', 'Refund & Cancellation Policy')

@section('content')
<div class="p-4">
    <article class="prose max-w-none">
        <h1>Refund &amp; Cancellation Policy</h1>
        <p>Effective and last updated: October 20, 2026.</p>

        <h2>What this covers</h2>
        <p>This policy covers our one-time paid listing (Premium Launch). Free badge listings involve no payment and have nothing to refund. Read this policy together with our <a href="{{ route('legal') }}#terms-of-use">Terms of Use</a> and the submission conditions on <a href="{{ route('products.create') }}">Add Product</a>.</p>
        <p>Paid listings provide a scheduled permanent listing and a dofollow product link without a badge requirement. Every tool remains subject to manual suitability review. Payment does not purchase an organic ranking or guarantee clicks, users, revenue, or social promotion.</p>

        <h2>Refunds and cancellation</h2>
        <ul>
            <li>Payments are generally non-refundable after publication or substantial delivery of paid benefits. Disappointing results or a change of mind after delivery do not qualify.</li>
            <li>Request cancellation before publication by email. We review eligibility individually; a cancellation request does not automatically stop a scheduled launch or guarantee a refund.</li>
            <li>Report duplicate or accidental payments within 14 days for a full-refund review.</li>
            <li>If our technical or operational failure prevents delivery, we will correct it or refund the undelivered service.</li>
            <li>If our team rejects your product before delivering paid benefits, you may request a refund.</li>
        </ul>

        <h2>Listing conditions</h2>
        <p>Listings are permanent subject to suitability review and our Terms of Use. Removing a badge automatically removes a badge listing from public view. Paid listings do not require a badge. Withholding or removing a paid listing before benefits are delivered is covered by the rejection provision above.</p>
        <p>Removal after delivery for deceptive information, unlawful products, fraudulent payments, or manipulated engagement does not normally qualify for a refund.</p>

        <h2>Requesting help</h2>
        <p>Email <a href="mailto:hello@softwareontheweb.com">hello@softwareontheweb.com</a> with your purchase email, product URL, payment date, receipt or transaction reference, and reason. Do not send card details. We aim to respond within two business days. Approved refunds use the original payment method; processor and bank timing varies.</p>
        <p>Contact us about payment problems so we can investigate. Nothing here limits mandatory consumer rights or your right to dispute a payment. Policy changes will show an updated date on this page.</p>
    </article>
</div>
@endsection
