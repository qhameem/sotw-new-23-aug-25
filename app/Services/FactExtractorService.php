<?php

namespace App\Services;

class FactExtractorService
{
    private array $errors = [];

    public function __construct(private ListingAiClient $client) {}

    public function errors(): array
    {
        return $this->errors;
    }

    public function extract(string $source): ?array
    {
        $this->errors = [];
        $prompt = $this->client->prompt('facts_extraction_prompt.txt', ['{sourceText}' => $source]);
        $required = ['product_name', 'product_type', 'one_line_description', 'platforms', 'minimum_os', 'price_amount', 'currency', 'pricing_model', 'free_plan', 'free_trial', 'license', 'target_user', 'top_features', 'integrations', 'data_storage', 'version', 'last_updated', 'maker_name', 'company_name', 'evidence'];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $facts = $this->client->json($prompt, 3000);
            $errors = [];
            if (! is_array($facts)) {
                $errors = array_merge(['AI provider returned no usable facts JSON.'], $this->client->errors());
            } else {
                foreach ($required as $field) {
                    if (! array_key_exists($field, $facts)) {
                        $facts[$field] = in_array($field, ['platforms', 'top_features', 'integrations', 'evidence'], true) ? [] : null;
                    }
                }
                if (! is_array($facts['evidence'])) $facts['evidence'] = [];
                foreach ($required as $field) {
                    if ($field === 'evidence') continue;
                    $value = $facts[$field];
                    $isList = in_array($field, ['platforms', 'top_features', 'integrations'], true);
                    $valid = $isList ? is_array($value) : (in_array($field, ['free_plan', 'free_trial'], true) ? is_bool($value) || $value === null : is_string($value) || $value === null);
                    if (! $valid) {
                        $facts[$field] = $isList ? [] : null;
                        continue;
                    }
                    if ($value === null || $value === '' || $value === []) continue;
                    $quote = $facts['evidence'][$field] ?? null;
                    $supported = is_string($quote) && trim($quote) !== '' && mb_stripos($source, $quote) !== false;
                    if (! $supported) {
                        $facts[$field] = $isList ? [] : null;
                        unset($facts['evidence'][$field]);
                    }
                }
                if (filled($facts['product_type']) || filled($facts['one_line_description']) || $facts['top_features'] !== []) {
                    $facts['top_features'] = array_slice($facts['top_features'], 0, 6);
                    return array_intersect_key($facts, array_flip($required));
                }
                $errors[] = 'No source-supported product type, description, or feature remained.';
            }
            $this->errors = $errors;
            $prompt .= "\nValidation errors to fix:\n".implode("\n", $errors);
        }

        return null;
    }
}
