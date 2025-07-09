<!DOCTYPE html>
<html>
<head>
    <title>Payment Confirmation</title>
</head>
<body>
    <h1>Payment Confirmation</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Your payment for the product "{{ $product->name }}" has been successfully processed.</p>
    <p>Your product is now approved and live on the site.</p>
    <p>Thank you for your payment!</p>
</body>
</html>