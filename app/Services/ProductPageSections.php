<?php

namespace App\Services;

use App\Models\Product;
use DOMDocument;
use DOMElement;

class ProductPageSections
{
    public function build(Product $product, array $editorial, array $description): array
    {
        $details = (string) ($description['details_html'] ?? '');
        $about = $description['overview_blocks'] ?? [];
        // A heading-first description has no introduction to split off.
        $raw = trim(preg_replace('/<!--.*?-->/s', '', $product->description ?? '') ?? '');
        if (preg_match('/^(?:<(?:div|section|article)\b[^>]*>\s*)*<h[23]\b/i', $raw)) {
            $about = [];
            $details = $raw;
        }
        $groups = [];
        if (! $product->usesProductFacts() && $details !== '') {
            [$groups, $details] = $this->extractGroups($details, ! empty($product->faq) || ! empty($editorial['faq']));
        }
        $features = $this->strings($product->features ?: ($editorial['key_features'] ?? []));
        $audience = $this->strings($product->best_for_text ? [$product->best_for_text] : ($editorial['ideal_for'] ?? []));
        $rows = [];
        foreach (['audience' => 'Who is it for?', 'problem' => 'Problem', 'solution' => 'Solution', 'unique' => 'What makes it unique'] as $key => $title) {
            $text = $key === 'audience' && $audience !== [] ? implode(' ', $audience) : ($groups[$key] ?? '');
            if ($text !== '') {
                $rows[] = ['key' => $key, 'title' => $title, 'text' => $text];
            }
        }

        return [
            'about_html' => $product->usesProductFacts() ? [] : $about,
            'overview' => $rows,
            'features' => $this->strings(array_merge($features, $groups['features'] ?? [])),
            'use_cases' => $this->strings(array_merge($editorial['top_use_cases'] ?? [], $groups['use_cases'] ?? [])),
            'details_html' => $product->usesProductFacts() ? '' : $details,
        ];
    }

    private function extractGroups(string $html, bool $hasSeparateFaq): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="section-root">'.$html.'</div>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $dom->getElementById('section-root');
        if (! $root) {
            return [[], $html];
        }
        foreach ((new \DOMXPath($dom))->query('//script|//style|//noscript') as $node) {
            $node->parentNode?->removeChild($node);
        }
        // Editors sometimes wrap the whole description in a single div.
        while ($root->childNodes->length === 1 && $root->firstChild instanceof DOMElement && in_array(strtolower($root->firstChild->tagName), ['div', 'section', 'article'], true)) {
            $root = $root->firstChild;
        }
        $chunks = [];
        $current = ['heading' => null, 'nodes' => []];
        foreach ($root->childNodes as $node) {
            if ($node instanceof DOMElement && in_array(strtolower($node->tagName), ['h2', 'h3'], true)) {
                $chunks[] = $current;
                $current = ['heading' => $node, 'nodes' => []];
            } else {
                $current['nodes'][] = $node;
            }
        }
        $chunks[] = $current;
        $groups = [];
        $remaining = '';
        foreach ($chunks as $chunk) {
            $heading = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $chunk['heading']?->textContent ?? '') ?? ''));
            $key = match (true) {
                in_array($heading, ['who is it for', 'ideal for', 'best for'], true), preg_match('/^who is .+ best for$/', $heading) === 1 => 'audience',
                in_array($heading, ['problem', 'the problem'], true) => 'problem',
                in_array($heading, ['solution', 'the solution'], true) => 'solution',
                in_array($heading, ['what makes it unique', 'what makes this unique'], true) => 'unique',
                $heading === 'key features', $heading === 'features', str_starts_with($heading, 'what are the key features of ') => 'features',
                in_array($heading, ['use cases', 'top use cases'], true), preg_match('/^what can you use .+ for$/', $heading) === 1 => 'use_cases',
                $hasSeparateFaq && (str_starts_with($heading, 'frequently asked questions') || $heading === 'faq') => 'faq',
                default => null,
            };
            $text = $this->strings(array_map(fn ($node) => $node->textContent, $chunk['nodes']));
            if ($key === 'faq' && $text !== []) {
                continue;
            }
            if ($key && $text !== []) {
                if (in_array($key, ['features', 'use_cases'], true)) {
                    $items = [];
                    foreach ($chunk['nodes'] as $node) {
                        if ($node instanceof DOMElement && in_array(strtolower($node->tagName), ['ul', 'ol'], true)) {
                            foreach ($node->getElementsByTagName('li') as $item) {
                                $items[] = $item->textContent;
                            }
                        } else {
                            $items[] = $node->textContent;
                        }
                    }
                    $groups[$key] = array_merge($groups[$key] ?? [], $this->strings($items));
                } else {
                    $groups[$key] = trim(($groups[$key] ?? '').' '.implode(' ', $text));
                }
            } else {
                $remaining .= $chunk['heading'] ? $dom->saveHTML($chunk['heading']) : '';
                foreach ($chunk['nodes'] as $node) {
                    $remaining .= $dom->saveHTML($node);
                }
            }
        }

        return [$groups, trim($remaining)];
    }

    private function strings(array $items): array
    {
        return array_values(array_unique(array_filter(array_map(function ($item) {
            if (is_array($item)) {
                $item = implode(' ', array_filter($item, 'is_scalar'));
            }

            return is_scalar($item) ? trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $item), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '') : '';
        }, $items))));
    }
}
