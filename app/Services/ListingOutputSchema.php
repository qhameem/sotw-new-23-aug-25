<?php

namespace App\Services;

class ListingOutputSchema
{
    public function forPrompt(string $prompt): ?array
    {
        $string = ['type' => 'string'];
        $strings = ['type' => 'array', 'items' => $string];
        $qa = ['type' => 'array', 'items' => $this->object(['question' => $string, 'answer' => $string])];

        return match (true) {
            str_contains($prompt, 'Write the listing body') => $this->object([
                'summary' => $string, 'features' => $strings, 'best_for' => $string, 'not_for' => $string,
                'top_use_cases' => $strings, 'comparisons' => $strings, 'integrations' => $strings,
                'pros' => $strings, 'editorial_qa' => $qa, 'faq' => $qa,
            ]),
            str_contains($prompt, 'Write three tagline candidates') => $this->object(['candidates' => $strings]),
            str_contains($prompt, 'Write SEO fields') => $this->object(['seo_title' => $string, 'meta_descriptions' => $strings]),
            str_contains($prompt, 'You check generated listing fields') => $this->object([
                'issues' => ['type' => 'array', 'items' => $this->object(['field' => $string, 'problem' => $string, 'source_quote' => $string])],
            ]),
            str_contains($prompt, 'Choose the most relevant categories') => $this->classification($string, $strings),
            str_contains($prompt, 'You extract facts from a software product website') => $this->facts($strings),
            default => null,
        };
    }

    private function object(array $properties): array
    {
        return ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false];
    }

    private function classification(array $string, array $strings): array
    {
        $picks = ['type' => 'array', 'items' => $this->object(['name' => $string, 'quote' => $string])];

        return $this->object([
            'categories' => $picks, 'use_cases' => $picks, 'best_for' => $picks, 'pricing' => $picks, 'platforms' => $picks,
            'suggestions' => $this->object(['categories' => $strings, 'use_cases' => $strings]),
        ]);
    }

    private function facts(array $strings): array
    {
        $properties = [];
        foreach (['product_name', 'product_type', 'one_line_description', 'minimum_os', 'price_amount', 'currency', 'pricing_model', 'license', 'target_user', 'data_storage', 'version', 'last_updated', 'maker_name', 'company_name'] as $field) {
            $properties[$field] = ['type' => ['string', 'null']];
        }
        foreach (['platforms', 'top_features', 'integrations'] as $field) {
            $properties[$field] = $strings;
        }
        foreach (['free_plan', 'free_trial'] as $field) {
            $properties[$field] = ['type' => ['boolean', 'null']];
        }
        $properties['evidence'] = $this->object(array_fill_keys(array_keys($properties), ['type' => ['string', 'null']]));

        return $this->object($properties);
    }
}
