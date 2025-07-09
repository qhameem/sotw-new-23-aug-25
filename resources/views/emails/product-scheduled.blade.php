<!DOCTYPE html>
<html>
<head>
    <title>Product Scheduled</title>
</head>
<body>
    <h1>Congratulations, {{ $user->name }}!</h1>
    <p>Your product, "{{ $product->name }}", has been successfully scheduled for publication on {{ $product->published_at->format('F d, Y') }}.</p>
    <p>You can view your product here: <a href="{{ route('products.show', $product->slug) }}">{{ route('products.show', $product->slug) }}</a></p>
    <p>Thank you for using our platform!</p>
</body>
</html>