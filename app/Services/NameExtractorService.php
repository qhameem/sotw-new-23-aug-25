<?php

namespace App\Services;

class NameExtractorService
{
    public function extract(string $title, string $url): string
    {
        $separators = ['|', '-', '–', '—', ':', '•'];
        $parts = preg_split('/(' . implode('|', array_map('preg_quote', $separators)) . ')/', $title);

        if (count($parts) > 1) {
            // When a title is split by a separator, the brand name is usually the shortest part.
            $shortestPart = null;
            foreach ($parts as $part) {
                $trimmedPart = trim($part);
                if (!empty($trimmedPart)) {
                    if ($shortestPart === null || strlen($trimmedPart) < strlen($shortestPart)) {
                        $shortestPart = $trimmedPart;
                    }
                }
            }
            return $shortestPart ?? $title;
        }

        return $title;
    }
}