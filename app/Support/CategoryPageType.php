<?php

namespace App\Support;

use App\Models\Category;

final class CategoryPageType
{
    public const CATEGORY = 'category';

    public const USE_CASE = 'use-case';

    public const BEST_FOR = 'best-for';

    public const PLATFORM = 'platform';

    public static function resolve(Category $category): string
    {
        $typeNames = $category->loadMissing('types')->types->pluck('name');

        if ($typeNames->intersect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE))->isNotEmpty()) {
            return self::CATEGORY;
        }

        if ($typeNames->intersect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))->isNotEmpty()) {
            return self::USE_CASE;
        }

        if ($typeNames->intersect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR))->isNotEmpty()) {
            return self::BEST_FOR;
        }

        if ($typeNames->intersect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM))->isNotEmpty()) {
            return self::PLATFORM;
        }

        return self::CATEGORY;
    }

    public static function routeName(string $type, bool $paginated = false): string
    {
        $base = match ($type) {
            self::USE_CASE => 'use-cases.show',
            self::BEST_FOR => 'best-for.show',
            self::PLATFORM => 'platforms.show',
            default => 'categories.show',
        };

        return $paginated ? $base.'.page' : $base;
    }

    public static function url(Category $category, array $parameters = []): string
    {
        $type = self::resolve($category);

        return route(self::routeName($type, isset($parameters['page'])), [
            'category' => $category->slug,
            ...$parameters,
        ]);
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::USE_CASE => 'Use cases',
            self::BEST_FOR => 'Best for',
            self::PLATFORM => 'Platforms',
            default => 'Categories',
        };
    }

    public static function heading(string $type, string $name): string
    {
        return match ($type) {
            self::USE_CASE => "Tools for {$name}",
            self::BEST_FOR => "Software for {$name}",
            self::PLATFORM => "{$name} Apps and Software",
            default => "{$name} Software",
        };
    }

    public static function intro(string $type, Category $category): string
    {
        $name = strip_tags($category->name);
        $detail = trim(strip_tags((string) $category->description));
        $lead = match ($type) {
            self::USE_CASE => "Find tools for {$name}, compare available options, and choose software suited to this workflow.",
            self::BEST_FOR => "Compare software for {$name}, with products selected for this audience and its priorities.",
            self::PLATFORM => "Discover {$name} apps and software, compare available products, and find the right platform-specific option.",
            default => "Explore {$name} software, compare published products, and find the right option for your needs.",
        };

        return $detail !== '' ? $lead.' '.$detail : $lead;
    }
}
