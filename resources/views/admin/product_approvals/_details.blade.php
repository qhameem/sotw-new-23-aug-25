@php
    $scheduled = !$product->is_published;
    $detailUrl = $scheduled ? route('admin.product-approvals.preview', $product) : route('products.show', $product->slug);
    $source = ['free' => 'Free/admin approval', 'badge' => 'Badge', 'paid' => 'Paid'][$product->submission_type] ?? ucfirst((string) $product->submission_type);
@endphp
<div id="product-detail-{{ $product->id }}" data-detail-content="{{ $product->id }}" @if(!$initial) hidden @endif>
    <header class="approval-detail-header">
        <div><h2>Product #{{ $product->id }}</h2><div class="approval-detail-meta"><span class="approval-status {{ $scheduled ? 'is-scheduled' : 'is-published' }}">{{ $scheduled ? 'Scheduled' : 'Published' }}</span><span>{{ $product->published_at?->copy()->timezone('UTC')->format('M j, H:i') ?? 'No publish date' }} UTC</span></div></div>
        <button type="button" data-detail-close aria-label="Close product details" class="approval-icon-button"><x-admin.approval-icon name="close" /></button>
    </header>
    <div class="approval-detail-identity">
        <img src="{{ $product->logo_url }}" alt="" class="approval-detail-logo" width="76" height="76">
        <h3>{{ $product->name }}</h3>
        <p>{{ $product->tagline }}</p>
        <a href="{{ $product->link }}" target="_blank" rel="noopener nofollow" class="approval-external-link">{{ parse_url($product->link, PHP_URL_HOST) ?: 'Visit website' }} <x-admin.approval-icon name="arrow" width="15" height="15" /></a>
    </div>
    <section class="approval-detail-section"><h3>Submission details</h3>
        <dl class="approval-detail-facts">
            <div><dt>Submitted by</dt><dd>{{ $product->user?->name ?: 'Unknown user' }} @if($product->user?->hasRole('admin'))<span class="approval-admin-tag">Admin</span>@endif</dd></div>
            <div><dt>Email</dt><dd>{{ $product->user?->email ?: '—' }}</dd></div>
            <div><dt>Submission</dt><dd>{{ $source }}</dd></div>
            <div><dt>Created</dt><dd>{{ $product->created_at?->copy()->timezone('UTC')->format('M j, Y') ?? '—' }}</dd></div>
        </dl>
    </section>
    <section class="approval-detail-section"><h3>Categories</h3><div class="approval-detail-categories">@forelse($product->categories as $category)<span>{{ $category->name }}</span>@empty<p>No categories assigned.</p>@endforelse</div></section>
    @if($product->submission_type === 'badge')
        <section class="approval-detail-section"><h3>Badge verification</h3><p class="approval-badge-result">{{ $product->badge_verified ? 'Verified' : ($product->badge_consecutive_failures > 0 ? 'Failed' : 'Pending') }}</p>
            <a href="{{ $product->badge_placement_url ?: $product->link }}" target="_blank" rel="noopener nofollow" class="approval-external-link">Badge page <x-admin.approval-icon name="arrow" width="15" height="15" /></a>
            <form method="POST" action="{{ route('admin.products.verify-badge', $product) }}">@csrf<button class="approval-text-button" type="submit">Check badge now</button></form>
        </section>
    @endif
    <footer class="approval-detail-actions">
        <a href="{{ route('admin.products.edit', $product) }}?from=approvals" class="approval-button-dark">Edit product</a>
        @if($scheduled)
            <form action="{{ route('admin.product-approvals.publish-scheduled-now') }}" method="POST">@csrf<input type="hidden" name="publish_scope" value="selected"><input type="hidden" name="products[]" value="{{ $product->id }}"><button type="submit" class="approval-button-yellow">Publish now</button></form>
        @else
            <a href="{{ $detailUrl }}" target="_blank" rel="noopener" class="approval-button-yellow">View product <x-admin.approval-icon name="arrow" width="16" height="16" /></a>
        @endif
        <button type="button" data-detail-disapprove="{{ $product->id }}" class="approval-detail-disapprove">Disapprove product</button>
    </footer>
</div>
