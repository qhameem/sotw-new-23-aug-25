<?php

namespace App\Services;

use Illuminate\Support\Str;

class SlugService
{
    /**
     * Generates a unique slug.
     *
     * @param string $name The string to generate a slug from.
     * @param callable $existsCheck A callable that returns true if the slug exists.
     * @return string The unique slug.
     */
    public function generateUniqueSlug(string $name, callable $existsCheck): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 2;

        while ($existsCheck($slug)) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}