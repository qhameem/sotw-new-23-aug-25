<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductMediaSeo
{
    public static function screenshotFilenameForUrl(string $url, string $extension = 'webp'): string
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? 'website'));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $hostLabel = Str::slug(preg_replace('/\.[a-z0-9-]+$/i', '', $host) ?: $host);

        $path = trim((string) ($parts['path'] ?? ''), '/');
        $pathLabel = $path === '' ? 'homepage' : Str::slug(str_replace('/', ' ', $path));

        return trim(implode('-', array_filter([$hostLabel, $pathLabel, 'screenshot'])), '-') . '.' . strtolower($extension);
    }

    public static function productMediaFilename(
        Product $product,
        string $kind = 'image',
        string $extension = 'webp',
        ?int $position = null
    ): string {
        $slug = $product->slug ?: Str::slug($product->name);

        return trim(implode('-', array_filter([
            $slug,
            Str::slug($kind),
            $position ? (string) $position : null,
        ])), '-') . '.' . strtolower($extension);
    }

    public static function productMediaAltText(
        Product $product,
        string $kind = 'image',
        ?int $position = null
    ): string {
        $name = trim($product->name);
        $tagline = trim((string) ($product->tagline ?: $product->product_page_tagline ?: ''));

        return match ($kind) {
            'logo' => $name . ' logo',
            'screenshot', 'homepage-screenshot' => $tagline !== ''
                ? "{$name} homepage screenshot - {$tagline}"
                : "{$name} homepage screenshot",
            'thumbnail' => $name . ' product thumbnail',
            default => $position
                ? "{$name} product image {$position}"
                : "{$name} product image",
        };
    }

    public static function isSeoFriendlyFilename(string $path): bool
    {
        $filename = strtolower(pathinfo($path, PATHINFO_FILENAME));

        if ($filename === '' || Str::startsWith($filename, ['screenshot_', 'image_', 'img_', 'file_'])) {
            return false;
        }

        return !preg_match('/^[a-f0-9]{24,}$/', str_replace('-', '', $filename));
    }
}
