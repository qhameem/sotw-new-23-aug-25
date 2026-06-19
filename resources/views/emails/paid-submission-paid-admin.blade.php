<p>A paid product submission has been completed.</p>

<p><strong>Product:</strong> {{ $product->name }}</p>
<p><strong>Product ID:</strong> {{ $product->id }}</p>
<p><strong>Schedule date:</strong> {{ optional($checkout->schedule_date)->format('F j, Y') ?? 'Pending' }}</p>
<p><strong>Payment amount:</strong> ${{ number_format(($checkout->amount_cents ?? 0) / 100, 2) }}</p>

<p>
    <a href="{{ route('admin.products.edit', $product) }}">Open product in admin</a>
</p>
