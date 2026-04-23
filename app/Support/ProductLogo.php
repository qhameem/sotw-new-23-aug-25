<?php

namespace App\Support;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class ProductLogo
{
    public const EAGER_LIMIT = 8;
    public const PRELOAD_LIMIT = 6;

    public static function url($product): ?string
    {
        if (!$product) {
            return null;
        }

        $logoUrl = $product->logo_url ?? null;

        if (is_string($logoUrl) && $logoUrl !== '') {
            return $logoUrl;
        }

        $link = $product->link ?? null;

        return $link
            ? 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($link)
            : null;
    }

    public static function loading(int $position): string
    {
        return $position <= self::EAGER_LIMIT ? 'eager' : 'lazy';
    }

    public static function fetchPriority(int $position): string
    {
        return $position <= self::EAGER_LIMIT ? 'high' : 'low';
    }

    public static function preloadUrls(iterable $products, int $limit = self::PRELOAD_LIMIT): array
    {
        $urls = [];

        foreach ($products as $product) {
            $url = self::url($product);

            if ($url && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }

            if (count($urls) >= $limit) {
                break;
            }
        }

        return $urls;
    }

    public static function productListItems($regularProducts, $promotedProducts): array
    {
        $promotedProductsList = self::items($promotedProducts);
        $regularProductsList = self::items($regularProducts);
        $finalProductList = [];
        $maxPosition = 0;

        foreach ($promotedProductsList as $product) {
            $position = (int) ($product->promoted_position ?? 0);

            if ($position > 0) {
                $finalProductList[$position - 1] = $product;
                $maxPosition = max($maxPosition, $position);
            }
        }

        $regularProductIndex = 0;
        $currentFinalListLength = count($finalProductList);
        $targetListSize = max($maxPosition, $currentFinalListLength + count($regularProductsList));

        for ($i = 0; $i < $targetListSize; $i++) {
            if (!isset($finalProductList[$i])) {
                if ($regularProductIndex < count($regularProductsList)) {
                    $finalProductList[$i] = $regularProductsList[$regularProductIndex];
                    $regularProductIndex++;
                } elseif ($i >= $maxPosition) {
                    break;
                }
            }
        }

        $finalProductList = array_filter($finalProductList, fn ($product) => $product !== null);
        ksort($finalProductList);

        return array_values($finalProductList);
    }

    public static function paginatedListItems($regularProducts, $premiumProducts): array
    {
        $finalProductList = self::items($regularProducts);
        $premiumProductsList = self::items($premiumProducts);

        if (!$premiumProductsList) {
            return $finalProductList;
        }

        $withPremiumProducts = [];
        $premiumProductIndex = 0;
        $productCount = 0;

        foreach ($finalProductList as $product) {
            $withPremiumProducts[] = $product;
            $productCount++;

            if ($productCount % 4 === 0 && $premiumProductIndex < count($premiumProductsList)) {
                $withPremiumProducts[] = $premiumProductsList[$premiumProductIndex];
                $premiumProductIndex++;
            }
        }

        return $withPremiumProducts;
    }

    public static function items($products): array
    {
        if ($products instanceof AbstractPaginator) {
            return array_values($products->items());
        }

        if ($products instanceof Collection) {
            return array_values($products->all());
        }

        if (is_array($products)) {
            return array_values($products);
        }

        return [];
    }
}
