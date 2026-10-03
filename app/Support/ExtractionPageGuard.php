<?php

namespace App\Support;

use InvalidArgumentException;

final class ExtractionPageGuard
{
    public static function isBlockedTitle(string $title): bool
    {
        $title = strtolower(trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return preg_match('/^(just a moment\s*[.!…]*|access denied|attention required.*|verify (you are|you’re|you\x{2019}re) human.*|vercel security checkpoint|403 forbidden|502 bad gateway|503 service unavailable)$/u', $title) === 1;
    }

    public static function assertUsable(string $html): void
    {
        preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $matches);
        if (self::isBlockedTitle($matches[1] ?? '')) {
            throw new InvalidArgumentException('The website returned a verification or access-denied page. Fill the product details manually.');
        }
    }
}
