<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class ProductFactsExtractor
{
    public function extract(?string $description, int $limit = 7): array
    {
        if (! filled(strip_tags((string) $description))) {
            return [];
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><div>'.(string) $description.'</div>');
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $facts = [];

        foreach ($xpath->query('//li') ?: [] as $item) {
            $heading = $xpath->query('preceding::h2[1]', $item)?->item(0)?->textContent ?? '';
            if (preg_match('/best for|who is|use .* for/i', $heading)) {
                continue;
            }

            $fact = trim(preg_replace('/\s+/', ' ', $item->textContent) ?? '');
            if ($fact !== '' && ! in_array($fact, $facts, true)) {
                $facts[] = $fact;
            }

            if (count($facts) >= $limit) {
                break;
            }
        }

        return $facts;
    }
}
