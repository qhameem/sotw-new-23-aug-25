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
    public function extract(string $title, string $url = ''): string
    {
        if (trim($title) === '') {
            return '';
        }

        // 1. Initial cleaning: Remove common trailing noise like " - Home", " | Official Site"
        $title = preg_replace('/\b(Home|Official Site|Login|Sign Up|Register|Landing Page|Website)\b/i', '', $title);

        // 2. Split by common major separators EXCEPT single hyphen (which might be part of a name like AI-Powered)
        // We only split by hyphen if it has spaces around it: "Product - Tagline"
        $parts = preg_split('/[|–—]| \- /u', $title);
        $parts = array_filter(array_map('trim', $parts));

        if (empty($parts)) {
            return $title;
        }

        // 3. Brand name is ALMOST ALWAYS the first part in modern titles (e.g. "Brand | Tagline")
        // or the last part (e.g. "Tagline - Brand"). We'll score them.
        $candidates = [];
        foreach ($parts as $index => $part) {
            $score = 100;

            // First part gets a big bonus
            if ($index === 0)
                $score += 50;
            // Last part gets a small bonus
            if ($index === count($parts) - 1 && $index > 0)
                $score += 20;

            // Penalty for generic/tool words
            if (preg_match('/\b(AI|Product|Tool|App|Software|Best|Waitlist)\b/i', $part))
                $score -= 30;

            // Penalty for being TOO short (1-2 chars) unless it's the only option
            if (strlen($part) <= 2)
                $score -= 40;

            // Penalty for being TOO long (likely a sentence/tagline)
            if (strlen($part) > 25)
                $score -= 20;

            $candidates[] = ['text' => $part, 'score' => $score];
        }

        // Sort by score descending
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return $candidates[0]['text'];
    }
}