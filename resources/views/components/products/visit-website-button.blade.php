@props([
    'product',
    'surface' => 'product_details',
    'label' => null,
    'fullWidth' => false,
    'showIcon' => true,
    'contentClass' => '',
])

@php
    $resolvedLabel = $label ?? 'Visit Website';
@endphp

<a
    href="{{ route('products.click', ['product' => $product->slug, 'surface' => $surface]) }}"
    target="_blank"
    rel="{{ \App\Support\OutboundLink::rel($product->link, 'product_link') }}"
    aria-label="{{ $resolvedLabel }}"
    data-cta="visit-website"
    {{ $attributes->class([
        'group inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-1.5 text-sm font-semibold text-gray-50 hover:bg-gray-700 transition-colors focus:outline-none',
        'w-full' => $fullWidth,
    ]) }}
>
    <span @class(['cta-content inline-flex items-center gap-1.5', $contentClass])>
        <span>{{ $resolvedLabel }}</span>

        @if($showIcon)
            <svg class="size-4 shrink-0 stroke-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        @endif
    </span>
</a>
