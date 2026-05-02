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
    $styleClasses = match ($styleVariant) {
        'default' => 'text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
        'primary' => 'text-white bg-primary-600 border border-primary-600 rounded-lg shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
        'soft' => 'text-gray-700 bg-primary-50 border border-primary-200 rounded-lg shadow-sm hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
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
    rel="noopener ugc noreferrer"
    aria-label="{{ $resolvedLabel }}"
    data-cta="visit-website"
    data-label-variant="{{ $labelVariant }}"
    data-style-variant="{{ $styleVariant }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-semibold',
        $styleClasses,
        'w-full' => $fullWidth,
    ]) }}
>
    <span>{{ $resolvedLabel }}</span>

    @if($showIcon)
        <svg class="size-4 shrink-0 stroke-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M7 17L17 7M17 7H8M17 7V16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    @endif
</a>
