<?php

namespace App\Services;

class SeoTitleBuilder
{
    public function build(string $name, string $tagline): ?string
    {
        $title = trim($name).': '.trim($tagline);

        return mb_strlen($title) <= 60 && app(OutputValidator::class)->text('seo_title', $title) === []
            ? $title : null;
    }
}
