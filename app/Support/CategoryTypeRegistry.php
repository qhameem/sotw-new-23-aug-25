<?php

namespace App\Support;

class CategoryTypeRegistry
{
    public const SOFTWARE = 'software';
    public const PRICING = 'pricing';
    public const BEST_FOR = 'best_for';
    public const PLATFORM = 'platform';

    public static function namesFor(string $bucket): array
    {
        return match ($bucket) {
            self::SOFTWARE => ['Software Categories', 'Software', 'Category'],
            self::PRICING => ['Pricing'],
            self::BEST_FOR => ['Best for'],
            self::PLATFORM => ['Platform'],
            default => [],
        };
    }

    public static function primaryNameFor(string $bucket): ?string
    {
        return self::namesFor($bucket)[0] ?? null;
    }

    public static function platformCategoryNames(): array
    {
        return [
            'android',
            'android app',
            'browser',
            'chrome',
            'chrome extension',
            'firefox',
            'ios',
            'ipad',
            'iphone',
            'linux',
            'mac',
            'mac app',
            'macos',
            'safari',
            'web',
            'web app',
            'windows',
        ];
    }
}
