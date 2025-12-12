<?php

namespace App\Services;

class NameExtractorService
{
    /**
     * Extracts the most likely product name from a page title.
     *
     * @param string $title The full title from the page's <title> tag.
     * @return string The extracted name.
     */
    public function extract(string $title): string
    {
        // Return early if the title is empty
        if (trim($title) === '') {
            return '';
        }

        // Split the title by common separators (hyphen, em dash, en dash, pipe)
        $parts = preg_split('/[|–—–-]/u', $title);

        // Trim whitespace from each part and remove any empty parts
        $parts = array_filter(array_map('trim', $parts));

        // If splitting results in no valid parts, return the original title
        if (empty($parts)) {
            return $title;
        }

        // Find the shortest part, assuming it's the most likely name
        $shortestPart = '';
        $minLength = PHP_INT_MAX;

        foreach ($parts as $part) {
            if (strlen($part) < $minLength) {
                $shortestPart = $part;
                $minLength = strlen($part);
            }
        }

        return $shortestPart;
    }
}