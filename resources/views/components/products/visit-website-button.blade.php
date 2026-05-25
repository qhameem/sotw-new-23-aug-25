@props([
    'product',
    'surface' => 'product_details',
    'labelVariant' => 'generic',
    'styleVariant' => 'soft',
    'label' => null,
    'fullWidth' => false,
    'showIcon' => true,
])

@php
    // Keep CTA variants centralized so future A/B tests only need to update this component.
    $spacingClasses = match ($styleVariant) {
        'tag' => 'px-0 pt-1 pb-0',
        'soft' => 'px-4 py-1.5',
        default => 'px-4 py-1',
    };

    $styleClasses = match ($styleVariant) {
        'default' => 'text-gray-700 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
        'primary' => 'text-white bg-primary-600 border border-primary-600 rounded-lg shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
        'soft' => 'text-gray-700 bg-white border border-gray-400 rounded-md hover:bg-gray-50',
        'tag' => 'border-x-0 border-t-0 border-b border-b-gray-400 bg-transparent text-gray-900 hover:border-b-gray-600 focus:outline-none focus:ring-0 focus:ring-offset-0',
        default => 'text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
    };

    $resolvedLabel = $label ?? match ($labelVariant) {
        'product_name' => "Visit {$product->name}",
        'product_name_website' => "Visit {$product->name} Website",
        'official_site' => "Visit Official {$product->name} Site",
        default => 'Visit Website',
    };
@endphp

<a
    href="{{ route('products.click', ['product' => $product->slug, 'surface' => $surface]) }}"
    target="_blank"
    rel="{{ \App\Support\OutboundLink::rel($product->link, 'product_link') }}"
    aria-label="{{ $resolvedLabel }}"
    data-cta="visit-website"
    data-label-variant="{{ $labelVariant }}"
    data-style-variant="{{ $styleVariant }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-1.5 text-sm font-semibold transition-colors',
        $spacingClasses,
        $styleClasses,
        'w-full' => $fullWidth,
    ]) }}
>
    <span>{{ $resolvedLabel }}</span>

    @if($showIcon)
        <svg class="size-4 shrink-0 stroke-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    @endif
</a>
