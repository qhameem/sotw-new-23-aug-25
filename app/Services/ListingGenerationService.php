<?php

namespace App\Services;

use App\Models\Category;
use App\Support\CategoryTypeRegistry;

class ListingGenerationService
{
    private array $lastErrors = [];

    private ?string $sourceHost = null;

    public function __construct(
        private ListingAiClient $ai,
        private FactExtractorService $facts,
        private PlatformDetector $platforms,
        private OutputValidator $validator,
        private SeoTitleBuilder $titles,
    ) {}

    public function generate(string $name, string $source, ?array $factsOverride = null): array
    {
        $this->sourceHost = preg_match('/^Source: (https?:\/\/[^\s]+)/', $source, $matches)
            ? parse_url($matches[1], PHP_URL_HOST)
            : null;
        $facts = $factsOverride ?? $this->facts->extract($source);
        if ($facts === null) {
            return $this->draft(array_merge(['facts extraction failed'], $this->facts->errors()));
        }
        $detected = $this->platforms->detect($source);
        $facts['platforms'] = $detected !== [] ? $detected : array_values(array_filter((array) $facts['platforms'], fn ($platform) => $platform !== 'Browser'));
        $factsJson = json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $base = ['facts_json' => $facts];
        $nonBlockingErrors = [];

        $taglineService = app(TaglineRewriterService::class);
        $tagline = $taglineService->rewriteFromFacts($name, $facts, $source);
        if ($tagline === null) {
            return $base + $this->draft(array_merge(['tagline failed validation'], $taglineService->listingErrors()));
        }
        $base['tagline'] = $tagline;
        $taglineProvider = $taglineService->listingProvider();
        if ($taglineProvider) {
            $base['tagline_generation'] = $this->generationAttribution($taglineProvider);
        }

        $descriptionService = app(DescriptionRewriterService::class);
        $description = $descriptionService->generateFromFacts($name, $facts, $source);
        if ($description === null) {
            return $base + $this->draft(array_merge(['description failed validation'], $descriptionService->listingErrors()));
        }
        $base += ['summary' => $description['summary'], 'features' => $description['features'], 'best_for_text' => $description['best_for'], 'not_for_text' => $description['not_for'], 'faq' => $description['faq'], 'description' => '<p>'.e($description['summary']).'</p>'];
        $descriptionProvider = $descriptionService->listingProvider();
        if ($descriptionProvider) {
            $base['description_generation'] = $this->generationAttribution($descriptionProvider);
        }

        $preferredTitle = $this->titles->build($name, $tagline);
        $seo = $this->generateField('seo_fields_prompt.txt', ['{factsJson}' => $factsJson], 'seo', $source, '', 2, $preferredTitle !== null);
        if ($seo === null) {
            $nonBlockingErrors = array_merge(['SEO failed validation'], $this->lastErrors);
            $base['seo_title'] = $preferredTitle;
            $base['meta_description'] = null;
        } else {
            $base['seo_title'] = $preferredTitle ?? $seo['seo_title'];
            $base['meta_description'] = $seo['meta_description'];
        }

        $classification = $this->classify($factsJson, $source, $facts['platforms']);
        if ($classification === null) {
            return $base + $this->draft(array_merge(['classification failed validation'], $this->lastErrors));
        }
        $base += $classification;

        $issues = $this->verify($base, $factsJson, $source);
        if ($issues === null) {
            return $base + $this->draft(array_merge(['verification failed'], $this->lastErrors));
        }
        if ($issues !== []) {
            $fields = array_unique(array_column($issues, 'field'));
            $feedback = implode("\n", array_map(fn ($issue) => ($issue['field'] ?? 'unknown').': '.($issue['problem'] ?? ''), $issues));
            if (array_intersect($fields, ['categories', 'useCases', 'bestFor', 'pricing', 'platforms'])) {
                $replacement = $this->classify($factsJson, $source, $facts['platforms'], $feedback, 1);
                if ($replacement !== null) {
                    $base = array_merge($base, $replacement);
                }
            }
            $regenerated = [];
            foreach ($fields as $field) {
                if (in_array($field, ['categories', 'useCases', 'bestFor', 'pricing', 'platforms'], true)) {
                    continue;
                }
                if (in_array($field, ['tagline', 'description', 'summary', 'features', 'best_for_text', 'not_for_text', 'faq', 'seo_title', 'meta_description'], true)) {
                    $kind = $field === 'tagline' ? 'tagline' : (in_array($field, ['seo_title', 'meta_description']) ? 'seo' : 'description');
                    if (isset($regenerated[$kind])) {
                        continue;
                    }
                    $regenerated[$kind] = true;
                    $file = ['tagline' => 'tagline_prompt.txt', 'seo' => 'seo_fields_prompt.txt', 'description' => 'description_listing_prompt.txt'][$kind];
                    $bindings = ['{productName}' => $name, '{factsJson}' => $factsJson];
                    $replacement = $kind === 'description'
                        ? app(DescriptionRewriterService::class)->generateFromFacts($name, $facts, $source, $feedback)
                        : $this->generateField($file, $bindings, $kind, $source, $feedback, 1, $kind === 'seo' && $this->titles->build($name, $base['tagline']) !== null);
                    if ($replacement !== null) {
                        if ($kind === 'tagline') {
                            $base['tagline'] = $replacement;
                        }
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
            $remainingIssues = $this->verify($base, $factsJson, $source);
            if ($remainingIssues === null) {
                return $base + $this->draft(array_merge(['verification unavailable after repair'], $this->lastErrors));
            }
            if ($remainingIssues !== []) {
                return $base + $this->draft([
                    'verification issues remain',
                    ...array_map(fn ($issue) => $issue['field'].': '.$issue['problem'], $remainingIssues),
                ]) + ['generation_issues' => $remainingIssues];
            }
        }
        if (filled($base['meta_description'] ?? null)) {
            $nonBlockingErrors = [];
        }
        if ($nonBlockingErrors !== []) {
            return $base + $this->draft($nonBlockingErrors);
        }

        $base['generation_status'] = 'ready';

        return $base;
    }

    private function generateField(string $file, array $bindings, string $kind, string $source, string $feedback = '', int $tries = 2, bool $titleAlreadyBuilt = false): mixed
    {
        $this->lastErrors = [];
        $prompt = $this->ai->prompt($file, $bindings);
        if ($feedback !== '') {
            $prompt .= "\nVerification issues to fix:\n".$feedback;
        }
        for ($attempt = 0; $attempt < $tries; $attempt++) {
            $result = $this->ai->json($prompt);
            $errors = $kind === 'seo' ? $this->seoSchemaErrors($result) : $this->validator->schema($kind, $result);
            if ($result === null) {
                $errors = array_merge($errors, $this->ai->errors());
            }
            if ($errors === []) {
                if ($kind === 'tagline') {
                    foreach ($result['candidates'] as $candidate) {
                        if (is_string($candidate) && $this->validator->text('tagline', $candidate, $source) === []) {
                            return $candidate;
                        }
                    }
                    $errors[] = 'No candidate passes tagline validation.';
                } elseif ($kind === 'description') {
                    $errors = array_merge($errors, $this->validator->text('summary', $result['summary'], $source));
                    if (count($result['features']) > 6) {
                        $errors[] = 'features must contain at most 6 items.';
                    }
                    if (count($result['faq']) > 4) {
                        $errors[] = 'faq must contain at most 4 items.';
                    }
                    if ($errors === []) {
                        return $result;
                    }
                } elseif ($kind === 'seo') {
                    $candidates = isset($result['meta_descriptions']) && is_array($result['meta_descriptions'])
                        ? $result['meta_descriptions']
                        : [$result['meta_description'] ?? null];
                    $candidateErrors = [];
                    $selected = null;
                    foreach ($candidates as $candidate) {
                        if (! is_string($candidate)) {
                            continue;
                        }
                        $currentErrors = $this->validator->text('meta_description', $candidate, $source);
                        if ($currentErrors === []) {
                            $selected = $candidate;
                            break;
                        }
                        $candidateErrors = array_merge($candidateErrors, $currentErrors);
                    }
                    $errors = $selected === null ? array_values(array_unique($candidateErrors ?: ['No meta description candidate passed validation.'])) : [];
                    if (! $titleAlreadyBuilt) {
                        $errors = array_merge($this->validator->text('seo_title', $result['seo_title'], $source), $errors);
                    }
                    if ($errors === []) {
                        return ['seo_title' => $result['seo_title'], 'meta_description' => $selected];
                    }
                }
            }
            $prompt .= "\nValidation errors to fix:\n".implode("\n", $errors).($feedback === '' ? '' : "\nVerification issues:\n".$feedback);
            $this->lastErrors = $errors;
        }

        return null;
    }

    private function classify(string $factsJson, string $source, array $detectedPlatforms, string $feedback = '', int $maxAttempts = 2): ?array
    {
        $this->lastErrors = [];
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
                    if (! is_array($result[$key])) {
                        continue 2;
                    }
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
            $this->lastErrors = array_merge(['Classification JSON schema is invalid.'], $result === null ? $this->ai->errors() : []);
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
        foreach ($available as $name) {
            $lookup[mb_strtolower($name)] = $name;
        }
        $canonical = [];
        foreach ($picks as $name) {
            $match = $lookup[mb_strtolower($name)] ?? null;
            if ($match !== null && ! in_array($match, $canonical, true)) {
                $canonical[] = $match;
            }
        }

        return $canonical;
    }

    private function verify(array $fields, string $factsJson, string $source): ?array
    {
        $this->lastErrors = [];
        $reviewFields = array_intersect_key($fields, array_flip([
            'tagline', 'description', 'summary', 'features', 'best_for_text', 'not_for_text',
            'faq', 'seo_title', 'meta_description', 'categories', 'useCases', 'bestFor', 'pricing', 'platforms',
        ]));
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
            if ($this->validator->schema('verification', $result) === []) {
                $issues = array_map(function (array $issue): array {
                    $field = preg_replace('/[.\[].*$/', '', trim($issue['field']));
                    $issue['field'] = match ($field) {
                        'use_cases' => 'useCases',
                        'best_for' => 'best_for_text',
                        'not_for' => 'not_for_text',
                        default => $field,
                    };

                    return $issue;
                }, $result['issues']);

                return array_values(array_filter($issues, function (array $issue) use ($reviewFields): bool {
                    $field = $issue['field'];
                    if (! array_key_exists($field, $reviewFields)) {
                        return false;
                    }
                    $value = $reviewFields[$field];
                    if ($value === null || $value === '' || $value === []) {
                        return false;
                    }
                    if (is_string($value) && preg_match('/^not (?:stated|specified|provided)[.!]?$/i', trim($value))) {
                        return false;
                    }
                    if (preg_match('/^(?:claim\s+)?["\']?not stated["\']?\s+(?:not supported|unsupported)/i', trim($issue['problem']))) {
                        return false;
                    }

                    return true;
                }));
            }
            $prompt .= "\nValidation errors to fix:\nverification.issues must be an array.";
            $this->lastErrors = array_merge(['Verification JSON schema is invalid.'], $result === null ? $this->ai->errors() : []);
        }

        return null;
    }

    private function draft(array $errors): array
    {
        \Illuminate\Support\Facades\Log::error('Generated listing moved to draft.', [
            'source_host' => $this->sourceHost,
            'stage' => $errors[0] ?? 'unknown',
            'errors' => $errors,
        ]);

        return ['generation_status' => 'draft', 'generation_noindex' => true, 'generation_review_required' => true, 'generation_errors' => $errors];
    }

    private function seoSchemaErrors(?array $result): array
    {
        if ($result === null) {
            return ['seo must be valid JSON object.'];
        }
        if (! is_string($result['seo_title'] ?? null)) {
            return ['seo.seo_title must be string.'];
        }
        if (is_string($result['meta_description'] ?? null)) {
            return [];
        }
        if (! is_array($result['meta_descriptions'] ?? null)) {
            return ['seo.meta_descriptions must be array.'];
        }
        if (count($result['meta_descriptions']) !== 3 || count(array_filter($result['meta_descriptions'], 'is_string')) !== 3) {
            return ['seo.meta_descriptions must contain three strings.'];
        }

        return [];
    }

    private function generationAttribution(string $provider): array
    {
        $router = app(AiProviderRoutingService::class);

        return [
            'provider' => $provider,
            'provider_label' => $router->providerLabel($provider),
            'model' => $router->modelFor($provider),
        ];
    }
}
