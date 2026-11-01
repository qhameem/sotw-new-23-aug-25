<?php

namespace App\Support;

class ComparisonUrl
{
    public static function forSlugs(string $first, string $second): string
    {
        $slugs = [$first, $second];
        sort($slugs, SORT_STRING);

        return route('pseo.compare', ['params' => implode('-vs-', $slugs)]);
    }
}
