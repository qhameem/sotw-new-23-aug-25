<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Type;
use App\Support\CategoryTypeRegistry;

class ListingGenerationService
{
    public function __construct(
        private ListingAiClient $ai,
        private FactExtractorService $facts,
        private PlatformDetector $platforms,
        private OutputValidator $validator,
        private SeoTitleBuilder $titles,
    ) {}

    public function generate(string $name, string $source, ?array $factsOverride = null): array
    {
        $facts = $factsOverride ?? $this->facts->extract($source);
        if ($facts === null) {
            return $this->draft(['facts extraction failed']);
        }
        $detected = $this->platforms->detect($source);
        $facts['platforms'] = $detected !== [] ? $detected : array_values(array_filter((array) $facts['platforms'], fn ($platform) => $platform !== 'Browser'));
        $factsJson = json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $base = ['facts_json' => $facts];

        $tagline = app(TaglineRewriterService::class)->rewriteFromFacts($name, $facts, $source);
        if ($tagline === null) return $base + $this->draft(['tagline failed validation']);
        $base['tagline'] = $tagline;

        $description = app(DescriptionRewriterService::class)->generateFromFacts($name, $facts, $source);
        if ($description === null) return $base + $this->draft(['description failed validation']);
        $base += ['summary' => $description['summary'], 'features' => $description['features'], 'best_for_text' => $description['best_for'], 'not_for_text' => $description['not_for'], 'faq' => $description['faq'], 'description' => '<p>'.e($description['summary']).'</p>'];

        $seo = $this->generateField('seo_fields_prompt.txt', ['{factsJson}' => $factsJson], 'seo', $source);
        if ($seo === null) return $base + $this->draft(['SEO failed validation']);
        $base['seo_title'] = $this->titles->build($name, $tagline) ?? $seo['seo_title'];
        $base['meta_description'] = $seo['meta_description'];

        $classification = $this->classify($factsJson, $source, $facts['platforms']);
        if ($classification === null) return $base + $this->draft(['classification failed validation']);
        $base += $classification;

        $issues = $this->verify($base, $factsJson, $source);
        if ($issues === null) return $base + $this->draft(['verification failed']);
        if ($issues !== []) {
            $fields = array_unique(array_column($issues, 'field'));
            $feedback = implode("\n", array_map(fn ($issue) => ($issue['field'] ?? 'unknown').': '.($issue['problem'] ?? ''), $issues));
            if (array_intersect($fields, ['categories', 'use_cases', 'useCases', 'best_for', 'bestFor', 'pricing', 'platforms'])) {
                $replacement = $this->classify($factsJson, $source, $facts['platforms'], $feedback, 1);
                if ($replacement !== null) $base = array_merge($base, $replacement);
            }
            $regenerated = [];
            foreach ($fields as $field) {
                if (in_array($field, ['categories', 'use_cases', 'useCases', 'best_for', 'bestFor', 'pricing', 'platforms'], true)) {
                    continue;
                }
                if (in_array($field, ['tagline', 'description', 'summary', 'features', 'best_for_text', 'not_for_text', 'faq', 'seo_title', 'meta_description'], true)) {
                    $kind = $field === 'tagline' ? 'tagline' : (in_array($field, ['seo_title', 'meta_description']) ? 'seo' : 'description');
                    if (isset($regenerated[$kind])) continue;
                    $regenerated[$kind] = true;
                    $file = ['tagline' => 'tagline_prompt.txt', 'seo' => 'seo_fields_prompt.txt', 'description' => 'description_listing_prompt.txt'][$kind];
                    $bindings = ['{productName}' => $name, '{factsJson}' => $factsJson];
                    $replacement = $kind === 'description'
                        ? app(DescriptionRewriterService::class)->generateFromFacts($name, $facts, $source, $feedback)
                        : $this->generateField($file, $bindings, $kind, $source, $feedback, 1);
                    if ($replacement !== null) {
                        if ($kind === 'tagline') $base['tagline'] = $replacement;
                        if ($kind === 'seo') {
                            $base['seo_title'] = $this->titles->build($name, $base['tagline']) ?? $replacement['seo_title'];
                            $base['meta_description'] = $replacement['meta_description'];
                        }
                        if ($kind === 'description') {
                            $base['summary'] = $replacement['summary'];
                            $base['description'] = '<p>'.e($replacement['summary']).'</p>';
                            $base['features'] = $replacement['features'];
                            $base['best_for_text'] = $replacement['best_for'];
                            $base['not_for_text'] = $replacement['not_for'];
                            $base['faq'] = $replacement['faq'];
                        }
                    }
                }
            }
            if ($this->verify($base, $factsJson, $source) !== []) return $base + $this->draft(['verification issues remain']);
        }
        $base['generation_status'] = 'ready';

        return $base;
    }

    private function generateField(string $file, array $bindings, string $kind, string $source, string $feedback = '', int $tries = 2): mixed
    {
        $prompt = $this->ai->prompt($file, $bindings);
        for ($attempt = 0; $attempt < $tries; $attempt++) {
            $result = $this->ai->json($prompt);
            $errors = $this->validator->schema($kind, $result);
            if ($errors === []) {
                if ($kind === 'tagline') {
                    foreach ($result['candidates'] as $candidate) {
                        if (is_string($candidate) && $this->validator->text('tagline', $candidate, $source) === []) return $candidate;
                    }
                    $errors[] = 'No candidate passes tagline validation.';
                } elseif ($kind === 'description') {
                    $errors = array_merge($errors, $this->validator->text('summary', $result['summary'], $source));
                    if (count($result['features']) < 4 || count($result['features']) > 6) $errors[] = 'features must contain 4 to 6 items.';
                    if (count($result['faq']) !== 4) $errors[] = 'faq must contain 4 items.';
                    if ($errors === []) return $result;
                } elseif ($kind === 'seo') {
                    $errors = array_merge($this->validator->text('seo_title', $result['seo_title'], $source), $this->validator->text('meta_description', $result['meta_description'], $source));
                    if ($result['seo_title_length'] !== mb_strlen($result['seo_title']) || $result['meta_description_length'] !== mb_strlen($result['meta_description'])) $errors[] = 'Reported SEO lengths do not match text.';
                    if ($errors === []) return $result;
                }
            }
            $prompt .= "\nValidation errors to fix:\n".implode("\n", $errors).($feedback === '' ? '' : "\nVerification issues:\n".$feedback);
        }

        return null;
    }

    private function classify(string $factsJson, string $source, array $detectedPlatforms, string $feedback = '', int $maxAttempts = 2): ?array
    {
        $names = function (string $type): array {
            return Category::whereHas('types', fn ($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor($type)))->pluck('name')->all();
        };
        $available = [
            'categories' => $names(CategoryTypeRegistry::SOFTWARE), 'use_cases' => $names(CategoryTypeRegistry::USE_CASE),
            'best_for' => $names(CategoryTypeRegistry::BEST_FOR), 'pricing' => $names(CategoryTypeRegistry::PRICING),
            'platforms' => $names(CategoryTypeRegistry::PLATFORM),
        ];
        $bindings = [
            '{categories}' => implode(', ', $available['categories']), '{useCases}' => implode(', ', $available['use_cases']),
            '{bestFor}' => implode(', ', $available['best_for']), '{pricingModels}' => implode(', ', $available['pricing']),
            '{platforms}' => implode(', ', $available['platforms']), '{factsJson}' => $factsJson, '{sourceText}' => $source,
        ];
        $prompt = $this->ai->prompt('category_classification_prompt.txt', $bindings).($feedback === '' ? '' : "\nVerification issues:\n".$feedback);
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $result = $this->ai->json($prompt);
            if (is_array($result) && isset($result['categories'], $result['use_cases'], $result['best_for'], $result['pricing'], $result['platforms'], $result['suggestions'])
                && is_array($result['suggestions']) && is_array($result['suggestions']['categories'] ?? null) && is_array($result['suggestions']['use_cases'] ?? null)) {
                $picks = [];
                foreach (['categories' => 3, 'use_cases' => 2, 'best_for' => 2, 'pricing' => 1, 'platforms' => 3] as $key => $cap) {
                    if (! is_array($result[$key])) continue 2;
                    $picks[$key] = $this->canonicalPicks(self::verifiedPicks($result[$key], $source, $cap), $available[$key]);
                }
                $aiPlatforms = array_values(array_filter($picks['platforms'], fn ($platform) => $platform !== 'Browser' || in_array('Browser', $detectedPlatforms, true)));
                $picks['platforms'] = array_slice($this->canonicalPicks(array_merge($detectedPlatforms, $aiPlatforms), $available['platforms']), 0, 3);
                $ids = fn ($items) => Category::whereIn('name', $items)->pluck('id')->all();
                return [
                    'categories' => $ids($picks['categories']), 'useCases' => $ids($picks['use_cases']),
                    'bestFor' => $ids($picks['best_for']), 'pricing' => $ids($picks['pricing']),
                    'platforms' => $ids($picks['platforms']),
                    'suggestedCategories' => $picks['categories'] === [] ? array_slice((array) ($result['suggestions']['categories'] ?? []), 0, 1) : [],
                    'suggestedUseCases' => $picks['use_cases'] === [] ? array_slice((array) ($result['suggestions']['use_cases'] ?? []), 0, 1) : [],
                ];
            }
            $prompt .= "\nValidation errors to fix:\nClassification JSON schema is invalid.";
        }

        return null;
    }

    public static function verifiedPicks(array $picks, string $source, int $cap): array
    {
        $valid = [];
        foreach ($picks as $pick) {
            if (is_array($pick) && is_string($pick['name'] ?? null) && is_string($pick['quote'] ?? null)
                && trim($pick['quote']) !== '' && mb_stripos($source, $pick['quote']) !== false) {
                $valid[] = $pick['name'];
            }
        }

        return array_slice(array_values(array_unique($valid)), 0, $cap);
    }

    private function canonicalPicks(array $picks, array $available): array
    {
        $lookup = [];
        foreach ($available as $name) $lookup[mb_strtolower($name)] = $name;
        $canonical = [];
        foreach ($picks as $name) {
            $match = $lookup[mb_strtolower($name)] ?? null;
            if ($match !== null && ! in_array($match, $canonical, true)) $canonical[] = $match;
        }

        return $canonical;
    }

    private function verify(array $fields, string $factsJson, string $source): ?array
    {
        $reviewFields = $fields;
        foreach (['categories', 'useCases', 'bestFor', 'pricing', 'platforms'] as $field) {
            if (! empty($fields[$field])) {
                $reviewFields[$field] = Category::whereIn('id', $fields[$field])->pluck('name')->all();
            }
        }
        $prompt = $this->ai->prompt('listing_verification_prompt.txt', [
            '{generatedFieldsJson}' => json_encode($reviewFields), '{factsJson}' => $factsJson, '{sourceText}' => $source,
        ]);
        for ($i = 0; $i < 2; $i++) {
            $result = $this->ai->json($prompt);
            if ($this->validator->schema('verification', $result) === []) return $result['issues'];
            $prompt .= "\nValidation errors to fix:\nverification.issues must be an array.";
        }

        return null;
    }

    private function draft(array $errors): array
    {
        return ['generation_status' => 'draft', 'generation_noindex' => true, 'generation_review_required' => true, 'generation_errors' => $errors];
    }
}
