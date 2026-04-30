<?php

namespace App\Services;

use App\Models\Product;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class ProductEditorialContentService
{
    public function extract(Product $product): array
    {
        return $this->parseHtml($product->description);
    }

    public function hasStructuredEditorialSignals(?string $html): bool
    {
        $parsed = $this->parseHtml($html);

        $signalScore = 0;

        if (!empty($parsed['headline'])) {
            $signalScore++;
        }

        if (!empty($parsed['summary'])) {
            $signalScore++;
        }

        if (count($parsed['key_features']) >= 3) {
            $signalScore++;
        }

        if (!empty($parsed['ideal_for'])) {
            $signalScore++;
        }

        if (!empty($parsed['top_use_cases'])) {
            $signalScore++;
        }

        if (!empty($parsed['pros']) || !empty($parsed['limitations'])) {
            $signalScore++;
        }

        return $signalScore >= 5;
    }

    public function parseHtml(?string $html): array
    {
        $result = [
            'headline' => null,
            'summary' => null,
            'key_features' => [],
            'ideal_for' => [],
            'top_use_cases' => [],
            'known_alternatives' => [],
            'integrations' => [],
            'pros' => [],
            'limitations' => [],
            'faq' => [],
        ];

        if (!is_string($html) || trim($html) === '') {
            return $result;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<!DOCTYPE html><html><body><div id="editorial-root">' . $html . '</div></body></html>';

        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrappedHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (!$loaded) {
            return $result;
        }

        $xpath = new DOMXPath($document);
        $root = $xpath->query("//*[@id='editorial-root']")->item(0);

        if (!$root instanceof DOMElement) {
            return $result;
        }

        $currentSection = null;

        foreach ($root->childNodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($node->tagName);

            if (in_array($tagName, ['h2', 'h3'], true)) {
                $currentSection = $this->mapSectionName($node->textContent);
                continue;
            }

            if ($currentSection === null && $tagName === 'p') {
                $text = $this->cleanText($node->textContent);

                if ($text === '') {
                    continue;
                }

                if ($result['headline'] === null) {
                    $result['headline'] = $text;
                    continue;
                }

                if ($result['summary'] === null) {
                    $result['summary'] = $text;
                }

                continue;
            }

            if ($currentSection === null) {
                continue;
            }

            if (in_array($currentSection, ['key_features', 'ideal_for', 'top_use_cases', 'known_alternatives', 'integrations'], true)) {
                if (in_array($tagName, ['ul', 'ol'], true)) {
                    $result[$currentSection] = array_merge($result[$currentSection], $this->extractListItems($node));
                }

                continue;
            }

            if ($currentSection === 'pros_cons' && in_array($tagName, ['ul', 'ol'], true)) {
                foreach ($this->extractListItems($node) as $item) {
                    $normalized = strtolower($item);

                    if (str_starts_with($normalized, 'pros:')) {
                        $result['pros'] = array_merge($result['pros'], $this->splitInlinePoints(substr($item, 5)));
                        continue;
                    }

                    if (str_starts_with($normalized, 'limitations:')) {
                        $result['limitations'] = array_merge($result['limitations'], $this->splitInlinePoints(substr($item, 12)));
                        continue;
                    }

                    if (str_starts_with($normalized, 'cons:')) {
                        $result['limitations'] = array_merge($result['limitations'], $this->splitInlinePoints(substr($item, 5)));
                    }
                }

                continue;
            }

            if ($currentSection === 'faq' && $tagName === 'dl') {
                $result['faq'] = array_merge($result['faq'], $this->extractFaqItems($node));
            }
        }

        foreach (['key_features', 'ideal_for', 'top_use_cases', 'known_alternatives', 'integrations', 'pros', 'limitations'] as $key) {
            $result[$key] = array_values(array_unique(array_filter(array_map([$this, 'cleanText'], $result[$key]))));
        }

        return $result;
    }

    private function mapSectionName(string $heading): ?string
    {
        $normalized = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $heading)));

        return match ($normalized) {
            'key features' => 'key_features',
            'ideal for' => 'ideal_for',
            'top use cases' => 'top_use_cases',
            'known alternatives' => 'known_alternatives',
            'integrations ecosystem', 'integrations ecosystem ' => 'integrations',
            'pros cons' => 'pros_cons',
            'frequently asked questions' => 'faq',
            default => null,
        };
    }

    private function extractListItems(DOMNode $listNode): array
    {
        $items = [];

        foreach ($listNode->childNodes as $child) {
            if (!$child instanceof DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $text = $this->cleanText($child->textContent);

            if ($text !== '') {
                $items[] = $text;
            }
        }

        return $items;
    }

    private function extractFaqItems(DOMNode $listNode): array
    {
        $faq = [];
        $question = null;

        foreach ($listNode->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($child->tagName);
            $text = $this->cleanText($child->textContent);

            if ($text === '') {
                continue;
            }

            if ($tagName === 'dt') {
                $question = $text;
                continue;
            }

            if ($tagName === 'dd' && $question !== null) {
                $faq[] = [
                    'question' => $question,
                    'answer' => $text,
                ];
                $question = null;
            }
        }

        return $faq;
    }

    private function splitInlinePoints(string $value): array
    {
        $value = $this->cleanText($value);

        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s*(?:;|•|\|)\s*/u', $value) ?: [];
        $parts = array_values(array_filter(array_map([$this, 'cleanText'], $parts)));

        if (!empty($parts)) {
            return $parts;
        }

        if (substr_count($value, ',') > 0 && substr_count($value, ',') <= 3 && !str_contains($value, '.')) {
            return array_values(array_filter(array_map([$this, 'cleanText'], explode(',', $value))));
        }

        return [$value];
    }

    private function cleanText(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));

        return trim((string) $value);
    }
}
