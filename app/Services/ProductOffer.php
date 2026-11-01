<?php

namespace App\Services;

use App\Models\Product;

class ProductOffer
{
    public function forProduct(Product $product): ?array
    {
        $facts = $product->facts_json ?? [];
        $explicitPrice = isset($facts['price_amount']) && is_numeric($facts['price_amount']);
        $price = $facts['price_amount'] ?? $product->price;
        $currency = $facts['currency'] ?? $product->currency;

        // A database default of zero does not establish a free plan.
        if (! is_numeric($price) || ! is_finite((float) $price) || (float) $price < 0
            || ((float) $price === 0.0 && ! $explicitPrice)
            || ! is_string($currency) || ! preg_match('/^[a-z]{3}$/iD', $currency)) {
            return null;
        }

        $url = $product->pricing_page_url ?: $product->link;

        return array_filter([
            '@type' => 'Offer',
            'price' => number_format((float) $price, 2, '.', ''),
            'priceCurrency' => strtoupper($currency),
            'url' => filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $url : null,
        ], fn ($value) => $value !== null);
    }

    public function label(Product $product): ?string
    {
        $offer = $this->forProduct($product);

        return $offer ? $offer['priceCurrency'].' '.$offer['price'] : null;
    }
}
