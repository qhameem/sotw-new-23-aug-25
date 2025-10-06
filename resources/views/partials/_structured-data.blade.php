@if(isset($products) && $products->count() > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "itemListElement": [
    @foreach($products as $product)
    {
      "@type": "ListItem",
      "position": {{ $loop->iteration }},
      "url": "{{ route('products.show', ['product' => $product->slug]) }}"
    }
    @if(!$loop->last),@endif
    @endforeach
  ]
}
</script>
@endif