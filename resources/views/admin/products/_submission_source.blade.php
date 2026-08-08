@php
    $sourceLabels = [
        'badge' => 'Scheduled via badge',
        'paid' => 'Scheduled via payment',
        'free' => 'Free/admin approval',
    ];
@endphp

<div class="space-y-2 text-sm">
    <span class="dashboard-badge">{{ $sourceLabels[$product->submission_type] ?? ucfirst((string) $product->submission_type) }}</span>
    @if($product->submission_type === 'badge')
        <div class="break-all text-xs text-slate-500">
            Badge page:
            <a href="{{ $product->badge_placement_url ?: $product->link }}" target="_blank" rel="noopener nofollow" class="text-indigo-600 hover:underline">
                {{ $product->badge_placement_url ?: $product->link }}
            </a>
        </div>
        <div class="text-xs {{ $product->badge_verified ? 'text-emerald-700' : 'text-red-700' }}">
            {{ $product->badge_verified ? 'Current verification: passed' : 'Current verification: failed' }}
            @if($product->badge_verified_at)
                · Last passed {{ $product->badge_verified_at->copy()->timezone('UTC')->format('M j, Y H:i') }} UTC
            @endif
        </div>
        <form method="POST" action="{{ route('admin.products.verify-badge', $product) }}">
            @csrf
            <button type="submit" class="dashboard-secondary-button">Check badge now</button>
        </form>
    @endif
</div>
