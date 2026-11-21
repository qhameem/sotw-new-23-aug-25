<?php

namespace App\Support;

class CategoryTypeRegistry
{
    public const SOFTWARE = 'software';
    public const SOFTWARE_NAME = 'Software';
    public const LEGACY_SOFTWARE_NAME = 'Software Categories';
    public const PRICING = 'pricing';
    public const BEST_FOR = 'best_for';
    public const USE_CASE = 'use_case';
    public const PLATFORM = 'platform';

    public static function namesFor(string $bucket): array
    {
        return match ($bucket) {
            self::SOFTWARE => [self::SOFTWARE_NAME, self::LEGACY_SOFTWARE_NAME, 'Category'],
            self::PRICING => ['Pricing'],
            self::BEST_FOR => ['Best for'],
            self::USE_CASE => ['Use Case', 'Use Cases'],
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
