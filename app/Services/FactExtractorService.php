<?php

namespace App\Services;

class FactExtractorService
{
    public function __construct(private ListingAiClient $client) {}

    public function extract(string $source): ?array
    {
        $prompt = $this->client->prompt('facts_extraction_prompt.txt', ['{sourceText}' => $source]);
        $required = ['product_name', 'product_type', 'one_line_description', 'platforms', 'minimum_os', 'price_amount', 'currency', 'pricing_model', 'free_plan', 'free_trial', 'license', 'target_user', 'top_features', 'integrations', 'data_storage', 'version', 'last_updated', 'maker_name', 'company_name', 'evidence'];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $facts = $this->client->json($prompt);
            $errors = [];
            if (! is_array($facts) || array_diff($required, array_keys($facts)) || ! is_array($facts['platforms'] ?? null) || ! is_array($facts['top_features'] ?? null) || ! is_array($facts['evidence'] ?? null)) {
                $errors[] = 'Facts JSON does not match the required schema.';
            } else {
                foreach ($required as $field) {
                    $value = $facts[$field];
                    $valid = in_array($field, ['platforms', 'top_features', 'integrations', 'evidence'], true)
                        ? is_array($value)
                        : (in_array($field, ['free_plan', 'free_trial'], true) ? is_bool($value) || $value === null : is_string($value) || $value === null);
                    if (! $valid) $errors[] = "Invalid type for $field.";
                }
                foreach ($facts as $field => $value) {
                    if ($field === 'evidence' || $value === null || $value === '' || $value === []) continue;
                    $quote = $facts['evidence'][$field] ?? null;
                    if (! is_string($quote) || $quote === '' || mb_stripos($source, $quote) === false) {
                        $errors[] = "Missing or unsupported evidence for $field.";
                    }
                }
            }
            if ($errors === []) {
                $facts['top_features'] = array_slice($facts['top_features'], 0, 6);
                return $facts;
            }
            $prompt .= "\nValidation errors to fix:\n".implode("\n", $errors);
        }

        return null;
    }
}
