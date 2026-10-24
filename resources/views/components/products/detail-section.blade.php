@props(['title', 'tone' => 'slate'])
<section {{ $attributes->class(['product-section']) }}>
    <h2 class="product-section-title" data-tone="{{ $tone }}"><span aria-hidden="true">+</span> {{ $title }}</h2>
    <div class="product-section-body">{{ $slot }}</div>
</section>
