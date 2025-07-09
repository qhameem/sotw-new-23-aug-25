<x-mail::message>
# Your Product has been Approved!

Hello {{ $product->user->name }},

We are pleased to inform you that your product, **{{ $product->name }}**, has been approved and is scheduled to be published on **{{ $product->published_at->format('F j, Y') }}**.

You can view your product here:
<x-mail::button :url="route('products.show', $product)">
View Product
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>