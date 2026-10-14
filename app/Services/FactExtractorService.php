<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

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
        $cacheKey = 'listing_facts:v2:'.hash('sha256', $source);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            \App\Support\ExtractionReport::current()->record('facts', 'Using cached source facts.', 'success');
            return $cached;
        }
        $prompt = $this->client->prompt('facts_extraction_prompt.txt', ['{sourceText}' => $source]);
        $required = ['product_name', 'product_type', 'one_line_description', 'platforms', 'minimum_os', 'price_amount', 'currency', 'pricing_model', 'free_plan', 'free_trial', 'license', 'target_user', 'top_features', 'integrations', 'data_storage', 'version', 'last_updated', 'maker_name', 'company_name', 'evidence'];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $facts = $this->client->json($prompt, 3000);
            $errors = [];
            $unsupported = [];
            if (! is_array($facts)) {
                $errors = array_merge(['AI provider returned no usable facts JSON.'], $this->client->errors());
            } else {
                foreach ($required as $field) {
                    if (! array_key_exists($field, $facts)) {
                        $facts[$field] = in_array($field, ['platforms', 'top_features', 'integrations', 'evidence'], true) ? [] : null;
                    }
                }
                if (! is_array($facts['evidence'])) {
                    $facts['evidence'] = [];
                }
                foreach ($required as $field) {
                    if ($field === 'evidence') {
                        continue;
                    }
                    $value = $facts[$field];
                    $isList = in_array($field, ['platforms', 'top_features', 'integrations'], true);
                    $valid = $isList ? is_array($value) : (in_array($field, ['free_plan', 'free_trial'], true) ? is_bool($value) || $value === null : is_string($value) || $value === null);
                    if (! $valid) {
                        $facts[$field] = $isList ? [] : null;

                        continue;
                    }
                    if ($value === null || $value === '' || $value === []) {
                        continue;
                    }
                    $quote = $facts['evidence'][$field] ?? null;
                    $supported = is_string($quote) && trim($quote) !== '' && self::containsQuote($source, $quote);
                    if (! $supported) {
                        $facts[$field] = $isList ? [] : null;
                        unset($facts['evidence'][$field]);
                        $unsupported[] = $field;
                    }
                }
                foreach ($unsupported as $field) \App\Support\ExtractionReport::current()->record('facts', $field.': discarded because source quote did not match.', 'warning');
                if (filled($facts['product_type']) || filled($facts['one_line_description']) || $facts['top_features'] !== []) {
                    $facts['top_features'] = array_slice($facts['top_features'], 0, 6);
                    $validated = array_intersect_key($facts, array_flip($required));
                    Cache::put($cacheKey, $validated, now()->addMinutes(max(1, (int) config('listing_generation.fact_cache_minutes', 1440))));

                    return $validated;
                }
                $errors[] = 'No source-supported product type, description, or feature remained.';
                if ($unsupported !== []) {
                    $errors[] = 'These fields had evidence quotes absent from the source: '.implode(', ', $unsupported).'. Copy short evidence quotes verbatim from the source, including exact words and punctuation. Set unsupported fields to null or [].';
                }
            }
            $this->errors = $errors;
            foreach ($errors as $error) \App\Support\ExtractionReport::current()->record('validation', $error, 'warning');
            $prompt .= "\nValidation errors to fix:\n".implode("\n", $errors);
        }

        return null;
    }

    private static function containsQuote(string $source, string $quote): bool
    {
        $normalize = static fn (string $text): string => trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        return mb_stripos($normalize($source), $normalize($quote)) !== false;
    }
}
