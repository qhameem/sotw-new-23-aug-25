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
        $resolvedKind = self::normalizedMediaKind($kind, $position);
        $resolvedPosition = $resolvedKind === 'homepage-screenshot' ? null : $position;

        return trim(implode('-', array_filter([
            $slug,
            Str::slug($resolvedKind),
            $resolvedPosition ? (string) $resolvedPosition : null,
        ])), '-') . '.' . strtolower($extension);
    }

    public static function productMediaAltText(
        Product $product,
        string $kind = 'image',
        ?int $position = null
    ): string {
        $name = trim($product->name);
        $descriptor = self::visualDescriptor(
            (string) ($product->product_page_tagline ?: $product->tagline ?: ''),
            $name
        );
        $resolvedKind = self::normalizedMediaKind($kind, $position);

        $altText = match ($resolvedKind) {
            'logo' => $name . ' logo',
            'homepage-screenshot' => $descriptor !== ''
                ? "{$name} homepage showing {$descriptor}"
                : "{$name} homepage screenshot",
            'thumbnail' => $name . ' product thumbnail',
            'product-screenshot' => $descriptor !== ''
                ? ($position
                    ? "{$name} interface screenshot {$position} showing {$descriptor}"
                    : "{$name} interface screenshot showing {$descriptor}")
                : ($position
                    ? "{$name} interface screenshot {$position}"
                    : "{$name} interface screenshot"),
            'video' => $descriptor !== ''
                ? ($position
                    ? "{$name} demo video {$position} showing {$descriptor}"
                    : "{$name} demo video showing {$descriptor}")
                : ($position
                    ? "{$name} product demo video {$position}"
                    : "{$name} product demo video"),
            default => $position
                ? "{$name} product image {$position}"
                : "{$name} product image",
        };

        return self::finalizeAltText($altText);
    }

    protected static function normalizedMediaKind(string $kind, ?int $position = null): string
    {
        return match ($kind) {
            'image', 'screenshot', 'homepage-screenshot' => ($position === null || $position <= 1)
                ? 'homepage-screenshot'
                : 'product-screenshot',
            default => $kind,
        };
    }

    protected static function finalizeAltText(string $altText): string
    {
        $altText = preg_replace('/\s+/', ' ', trim($altText)) ?? trim($altText);

        return Str::limit($altText, 177, '...');
    }

    protected static function visualDescriptor(string $descriptor, string $productName = ''): string
    {
        $descriptor = trim(strip_tags($descriptor));
        $descriptor = preg_replace('/\s+/', ' ', $descriptor) ?? $descriptor;
        $descriptor = rtrim($descriptor, " \t\n\r\0\x0B.!?,;:");
        $productLead = trim($productName);

        if ($productLead !== '') {
            $descriptor = preg_replace('/^' . preg_quote($productLead, '/') . '\s+/iu', '', $descriptor) ?? $descriptor;
        }

        $descriptor = preg_replace('/^(helps you|help you|lets you|let you|allows you to|allow you to|allows|allow|enables you to|enable you to|enables|enable)\s+/i', '', $descriptor) ?? $descriptor;
        $descriptor = preg_replace('/^(helps?|lets?|allows?|enables?)\s+(teams?|users?|companies|businesses|creators|developers|marketers|sales teams?|support teams?)\s+(?:to\s+)?/i', '', $descriptor) ?? $descriptor;

        if (preg_match('/^(identify|detect|find)\s+and\s+(resolve|fix)\s+(.+?)\s+issues?\s+before\b.*?\b(?:your|the)\s+([^,]+?)(?:,.*)?$/i', $descriptor, $matches)) {
            $descriptor = trim($matches[4] . ' ' . $matches[3] . ' monitoring');
        }

        if (preg_match('/^(build|create)\s+and\s+(launch|ship)\s+(.+?)\s+faster\s+by\s+automating\s+workflows?.*$/i', $descriptor, $matches)) {
            $subject = trim($matches[3]);
            $descriptor = preg_match('/\bproducts?\b/i', $subject)
                ? 'product planning and workflow automation'
                : $subject . ' workflow automation';
        }

        $descriptor = preg_split('/\b(before|ensuring|allowing|while|without|so that|so you can|and more|so users can|by)\b/i', $descriptor)[0] ?? $descriptor;
        $descriptor = trim($descriptor, " \t\n\r\0\x0B,.;:-");

        $rewrites = [
            '/^(identify|detect|find)\s+and\s+(resolve|fix)\s+(.+?)\s+issues?$/i' => '$3 monitoring',
            '/^(monitor)\s+(.+)$/i' => '$2 monitoring',
            '/^(track)\s+(.+)$/i' => '$2 tracking',
            '/^(manage)\s+(.+)$/i' => '$2 management',
            '/^(automate)\s+(.+)$/i' => '$2 automation',
            '/^(analyze)\s+(.+)$/i' => '$2 analysis',
            '/^(organize)\s+(.+)$/i' => '$2 organization',
            '/^(optimize)\s+(.+)$/i' => '$2 optimization',
        ];

        foreach ($rewrites as $pattern => $replacement) {
            if (preg_match($pattern, $descriptor)) {
                $descriptor = preg_replace($pattern, $replacement, $descriptor) ?? $descriptor;
                break;
            }
        }

        $descriptor = preg_replace('/\bissues monitoring$/i', 'monitoring', $descriptor) ?? $descriptor;
        $descriptor = preg_replace('/\s+/', ' ', trim($descriptor)) ?? $descriptor;
        $truncatedDescriptor = Str::limit($descriptor, 80, '');

        if ($truncatedDescriptor !== $descriptor && str_contains($truncatedDescriptor, ' ')) {
            $descriptor = preg_replace('/\s+\S*$/', '', rtrim($truncatedDescriptor)) ?: rtrim($truncatedDescriptor);
        } else {
            $descriptor = rtrim($truncatedDescriptor);
        }

        return trim($descriptor, " \t\n\r\0\x0B,.;:-");
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
