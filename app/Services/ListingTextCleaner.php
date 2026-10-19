<?php

namespace App\Services;

class ListingTextCleaner
{
    public function clean(array $body): array
    {
        foreach ($body as $key => $value) {
            if (is_array($value)) {
                $body[$key] = $this->clean($value);
            } elseif (is_string($value)) {
                $body[$key] = $this->text($value);
            }
        }

        return $body;
    }

    private function text(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~isu', '', $text) ?? $text;
        $text = preg_replace('~</?(?:p|div|li|br|h[1-6])\b[^>]*>~iu', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = preg_replace('/!?\[([^\]]*)\]\(([^\s)]+)\)/u', '$1 ($2)', $text) ?? $text;
        $text = preg_replace('/^\s*#{1,6}\s+|^\s*\*\s+/mu', '', $text) ?? $text;
        $text = preg_replace('/\*{1,3}([^*]+)\*{1,3}/u', '$1', $text) ?? $text;
        $text = str_replace(['—', ';'], [', ', ','], $text);
        $text = preg_replace('/[\x{1F300}-\x{1FAFF}\x{FE0F}\x{200D}]/u', '', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
