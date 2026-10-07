<?php

namespace App\Support;

class GeneratedCopy
{
    public const PUNCTUATION_INSTRUCTION = 'Never use em dashes (U+2014). Use commas, periods, colons, or parentheses instead.';

    public static function withoutEmDashes(string $text): string
    {
        return preg_replace('/\s*(?:—|&mdash;|&#0*8212;|&#x0*2014;)\s*/iu', ', ', $text) ?? $text;
    }
}
