<!DOCTYPE html>
<html>
<head>
    <title>Your Paid Submission Receipt</title>
</head>
<body>
    @php
        $referenceId = 'TXN-' . str_pad((string) $checkout->id, 9, '0', STR_PAD_LEFT);
        $publishAt = $product->published_at ?: ($checkout->schedule_date ? \App\Support\ProductPublishSchedule::forDate($checkout->schedule_date) : null);
        $confirmationUrl = route('stripe.paid-submission.confirmation', $checkout);
        $productUrl = $product->slug ? route('products.show', $product->slug) : null;
    @endphp

    <h1>Your Paid Submission Receipt</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Your payment for "{{ $product->name }}" has been confirmed.</p>
    <p><strong>Transaction ID:</strong> {{ $referenceId }}</p>
    <p><strong>Confirmation number:</strong> {{ $checkout->uuid }}</p>
    <p><strong>Amount paid:</strong> ${{ number_format(($checkout->amount_cents ?? 0) / 100, 2) }} {{ strtoupper((string) ($checkout->currency ?? 'usd')) }}</p>
    <p><strong>Payment date:</strong> {{ $checkout->paid_at?->copy()->utc()->format('F j, Y, H:i \U\T\C') ?? now()->utc()->format('F j, Y, H:i \U\T\C') }}</p>
    <p><strong>Product publish date:</strong> {{ $publishAt?->copy()->utc()->format('F j, Y, H:i \U\T\C') ?? 'Not scheduled' }}</p>
    <p>You can change the publish date once from your confirmation page before the product goes live.</p>
    <p><a href="{{ $confirmationUrl }}">Open your confirmation page</a></p>

    @if($productUrl)
        <p><a href="{{ $productUrl }}">View your product page</a></p>
    @endif
</body>
</html>
