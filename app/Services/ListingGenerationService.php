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
        $report = \App\Support\ExtractionReport::current();
        $this->sourceHost = preg_match('/^Source: (https?:\/\/[^\s]+)/', $source, $matches)
            ? parse_url($matches[1], PHP_URL_HOST)
            : null;
        $errors = [];
        $facts = $factsOverride ?? $this->stage('facts', fn () => $this->facts->extract($source), $errors, fn () => $this->facts->errors());
        $facts ??= ['product_name' => $name, 'platforms' => [], 'top_features' => [], 'integrations' => [], 'evidence' => []];
        $detected = $this->stage('platform detection', fn () => $this->platforms->detect($source), $errors) ?? [];
        $facts['platforms'] = $detected !== [] ? $detected : array_values(array_filter((array) ($facts['platforms'] ?? []), fn ($platform) => $platform !== 'Browser'));
        $report->record('facts', count(array_filter($facts, fn ($value) => $value !== null && $value !== '' && $value !== [])).' populated fact fields (including evidence).');
        $factsJson = json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $base = ['facts_json' => $facts, 'field_errors' => []];

        $taglineService = app(TaglineRewriterService::class);
        $tagline = $this->stage('tagline', fn () => $taglineService->rewriteFromFacts($name, $facts, $source), $errors, fn () => $taglineService->listingErrors());
        if ($tagline !== null) {
            $report->record('tagline', 'Validated tagline: '.$tagline, 'success');
            $base['tagline'] = $tagline;
            $provider = $taglineService->listingProvider();
            if ($provider) {
                $base['tagline_generation'] = $this->generationAttribution($provider);
            }
        }

        $descriptionService = app(DescriptionRewriterService::class);
        $description = $this->stage('description', fn () => $descriptionService->generateFromFacts($name, $facts, $source), $errors, fn () => $descriptionService->listingErrors());
        if ($description !== null) {
            $report->record('description', 'Validated summary; '.count($description['features']).' features; '.count($description['pros'] ?? []).' pros; '.count($description['faq']).' FAQs.', 'success');
            $base = array_merge($base, \App\Support\ListingDescription::fields($description));
            $provider = $descriptionService->listingProvider();
            if ($provider) {
                $base['description_generation'] = $this->generationAttribution($provider);
            }
        }

        $preferredTitle = $this->stage('title building', fn () => $this->titles->build($name, $tagline ?? ''), $errors, optional: true);
        $seo = $this->stage('SEO', fn () => $this->generateField('seo_fields_prompt.txt', ['{factsJson}' => $factsJson], 'seo', $source, '', 2, $preferredTitle !== null), $errors, fn () => $this->lastErrors);
        $base['seo_title'] = $preferredTitle ?? ($seo['seo_title'] ?? null);
        $base['meta_description'] = $seo['meta_description'] ?? null;

        $classification = $this->stage('classification', fn () => $this->classify($factsJson, $source, $facts['platforms']), $errors, fn () => $this->lastErrors);
        if ($classification !== null) {
            foreach (['categories', 'useCases', 'bestFor', 'pricing', 'platforms'] as $field) {
                $report->record('classification', $field.': '.count($classification[$field]).' results.', $classification[$field] ? 'success' : 'warning');
            }
            $base = array_merge($base, $classification);
        } else {
            $base['field_errors'] += array_fill_keys(['categories', 'useCases', 'bestFor', 'pricing', 'platforms'], 'Classification failed. Regenerate or select these fields manually.');
        }

        $issues = $this->stage('verification', fn () => $this->verify($base, $factsJson, $source), $errors, fn () => $this->lastErrors);
        if ($issues) {
            foreach ($issues as $issue) {
                $report->record('verification', $issue['field'].': '.$issue['problem'], 'warning');
            }
            $feedback = implode("\n", array_map(fn ($issue) => $issue['field'].': '.$issue['problem'], $issues));
            $kinds = [];
            foreach (array_unique(array_column($issues, 'field')) as $field) {
                $kind = match (true) {
                    in_array($field, ['categories', 'useCases', 'bestFor', 'pricing', 'platforms'], true) => 'classification',
                    $field === 'tagline' => 'tagline',
                    in_array($field, ['seo_title', 'meta_description'], true) => 'SEO',
                    default => 'description',
                };
                $kinds[$kind] = true;
            }
            foreach (array_keys($kinds) as $kind) {
                $replacement = $this->stage($kind.' repair', fn () => match ($kind) {
                    'classification' => $this->classify($factsJson, $source, $facts['platforms'], $feedback, 1),
                    'tagline' => $this->generateField('tagline_prompt.txt', ['{productName}' => $name, '{factsJson}' => $factsJson], 'tagline', $source, $feedback, 1),
                    'SEO' => $this->generateField('seo_fields_prompt.txt', ['{factsJson}' => $factsJson], 'seo', $source, $feedback, 1, $preferredTitle !== null),
                    'description' => $descriptionService->generateFromFacts($name, $facts, $source, $feedback),
                }, $errors);
                if ($replacement === null) {
                    continue;
                }
                if ($kind === 'tagline') {
                    $base['tagline'] = $replacement;
                } elseif ($kind === 'description') {
                    $base = array_merge($base, \App\Support\ListingDescription::fields($replacement));
                } else {
                    $base = array_merge($base, $replacement);
                }
                if ($kind === 'SEO') {
                    $base['seo_title'] = $preferredTitle ?? $replacement['seo_title'];
                }
            }
            $remaining = $this->stage('verification after repair', fn () => $this->verify($base, $factsJson, $source), $errors, fn () => $this->lastErrors);
            if ($remaining) {
                $base['generation_issues'] = $remaining;
                $errors['verification issues'] = array_map(fn ($issue) => $issue['field'].': '.$issue['problem'], $remaining);
            }
        }

        foreach (['tagline', 'description', 'SEO'] as $stage) {
            if (isset($errors[$stage])) {
                $base['field_errors'][$stage === 'SEO' ? 'meta_description' : $stage] = implode(' ', $errors[$stage]);
            }
        }
        if ($errors !== []) {
            return array_merge($base, $this->draft(array_merge(...array_values($errors))));
        }
        $report->record('generation', 'All generation stages passed validation and verification.', 'success');
        $base['generation_status'] = 'ready';

        return $base;
    }

    private function stage(string $stage, callable $work, array &$errors, ?callable $details = null, bool $optional = false): mixed
    {
        $result = \App\Support\ExtractionReport::current()->attempt($stage, $work);
        if ($result === null && ! $optional) {
            $errors[$stage] = array_merge([$stage.' failed; remaining stages continued.'], $details ? $details() : []);
        }

        return $result;
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
            foreach ($errors as $error) {
                \App\Support\ExtractionReport::current()->record('validation', $error, 'warning');
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
                foreach ($result['pricing'] as $pick) {
                    if (! is_array($pick)) {
                        continue;
                    }
                    $name = is_string($pick['name'] ?? null) ? $pick['name'] : 'unknown';
                    $quote = is_string($pick['quote'] ?? null) ? $pick['quote'] : '';
                    $accepted = in_array($name, self::verifiedPicks([$pick], $source, 1), true)
                        && $this->canonicalPicks([$name], $available['pricing']) !== [];
                    \App\Support\ExtractionReport::current()->record('pricing classification evidence',
                        $name.': '.($accepted ? 'source quote matched' : 'rejected; quote missing or category unavailable').'; quote: '.json_encode($quote, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        $accepted ? 'info' : 'warning');
                }
                $aiPlatforms = array_values(array_filter($picks['platforms'], fn ($platform) => $platform !== 'Browser' || in_array('Browser', $detectedPlatforms, true)));
                $picks['platforms'] = array_slice($this->canonicalPicks(array_merge($detectedPlatforms, $aiPlatforms), $available['platforms']), 0, 3);
                $ids = fn ($items) => Category::whereIn('name', $items)->pluck('id')->all();
                $fieldErrors = [];
                foreach (['categories' => 'categories', 'use_cases' => 'useCases', 'pricing' => 'pricing'] as $key => $field) {
                    if ($picks[$key] !== []) {
                        $fieldErrors[$field] = '';
                    } elseif ($available[$key] === []) {
                        $fieldErrors[$field] = 'No configured options are available. Ask an administrator to configure them.';
                    } elseif ($result[$key] === []) {
                        $fieldErrors[$field] = $key === 'pricing'
                            ? 'No explicit pricing evidence was identified in the collected pages. Select pricing manually or provide a pricing page.'
                            : 'No supported match was identified among the available options. Select an option or add a custom suggestion.';
                    } elseif (self::verifiedPicks($result[$key], $source, 3) === []) {
                        $fieldErrors[$field] = 'Suggested options were rejected because their evidence quotes could not be found in the collected pages.';
                    } else {
                        $fieldErrors[$field] = 'Supported suggestions did not match the configured options. Select an option or add a custom suggestion.';
                    }
                }

                return [
                    'categories' => $ids($picks['categories']), 'useCases' => $ids($picks['use_cases']),
                    'bestFor' => $ids($picks['best_for']), 'pricing' => $ids($picks['pricing']),
                    'platforms' => $ids($picks['platforms']),
                    'field_errors' => $fieldErrors,
                    'suggestedCategories' => $picks['categories'] === [] ? array_slice((array) ($result['suggestions']['categories'] ?? []), 0, 1) : [],
                    'suggestedUseCases' => $picks['use_cases'] === [] ? array_slice((array) ($result['suggestions']['use_cases'] ?? []), 0, 1) : [],
                ];
            }
            \App\Support\ExtractionReport::current()->record('classification', 'Invalid classification schema; retrying.', 'warning');
            $prompt .= "\nValidation errors to fix:\nClassification JSON schema is invalid.";
            $this->lastErrors = array_merge(['Classification JSON schema is invalid.'], $result === null ? $this->ai->errors() : []);
        }

        return null;
    }

    public static function verifiedPicks(array $picks, string $source, int $cap): array
    {
        $valid = [];
        $normalize = static fn (string $text): string => trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $source = $normalize($source);
        foreach ($picks as $pick) {
            if (is_array($pick) && is_string($pick['name'] ?? null) && is_string($pick['quote'] ?? null)
                && $normalize($pick['quote']) !== '' && mb_stripos($source, $normalize($pick['quote'])) !== false) {
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
            'faq', 'top_use_cases', 'comparisons', 'integrations', 'pros', 'editorial_qa', 'seo_title', 'meta_description', 'categories', 'useCases', 'bestFor', 'pricing', 'platforms',
        ]));
        foreach (['categories', 'useCases', 'bestFor', 'pricing', 'platforms'] as $field) {
            if (! empty($fields[$field])) {
                $reviewFields[$field] = Category::whereIn('id', $fields[$field])->pluck('name')->all();
            }
        }
        $pricingService = app(PricingEvidenceService::class);
        $billingEvidence = $pricingService->extract($source);
        $supportedPricing = [];
        foreach ($reviewFields['pricing'] ?? [] as $name) {
            $quotes = $pricingService->supportingQuotes($name, $billingEvidence);
            if ($quotes !== []) {
                $supportedPricing[$name] = $quotes;
            }
        }
        if (! empty($reviewFields['pricing'])) {
            $report = \App\Support\ExtractionReport::current();
            $report->record('pricing verification evidence', 'Selected categories: '.implode(', ', $reviewFields['pricing']));
            foreach ($billingEvidence as $kind => $quotes) {
                foreach ($quotes as $quote) {
                    $report->record('pricing verification evidence', $kind.'; source: '.($quote['source_url'] ?? 'unlabelled').'; quote: '.json_encode($quote['quote'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'; context: '.json_encode($quote['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }
            if (! array_filter($billingEvidence)) {
                $report->record('pricing verification evidence', 'No recognized billing quotes found; relying on semantic source review.', 'warning');
            }
        }
        $prompt = $this->ai->prompt('listing_verification_prompt.txt', [
            '{generatedFieldsJson}' => json_encode($reviewFields), '{factsJson}' => $factsJson, '{sourceText}' => $source,
            '{pricingEvidenceJson}' => json_encode(['billing_quotes' => $billingEvidence, 'supported_category_candidates' => $supportedPricing], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

                foreach ($issues as $issue) {
                    if ($issue['field'] !== 'pricing') {
                        continue;
                    }
                    $quote = is_string($issue['source_quote'] ?? null) ? $issue['source_quote'] : '';
                    $normalizedSource = preg_replace('/\s+/u', ' ', $source);
                    $normalizedQuote = trim(preg_replace('/\s+/u', ' ', $quote));
                    $matches = $normalizedQuote !== '' && mb_stripos($normalizedSource, $normalizedQuote) !== false;
                    \App\Support\ExtractionReport::current()->record('pricing verification verdict',
                        $issue['problem'].'; verifier quote: '.json_encode($quote, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'; '.($matches ? 'quote matched collected source' : 'no matching source quote provided'), 'warning');
                }

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
            \App\Support\ExtractionReport::current()->record('verification', 'Invalid verification schema; retrying.', 'warning');
            $prompt .= "\nValidation errors to fix:\nverification.issues must be an array.";
            $this->lastErrors = array_merge(['Verification JSON schema is invalid.'], $result === null ? $this->ai->errors() : []);
        }

        return null;
    }

    private function draft(array $errors): array
    {
        foreach ($errors as $error) {
            \App\Support\ExtractionReport::current()->record('generation draft', $error, 'error');
        }
        \Illuminate\Support\Facades\Log::error('Generated listing moved to draft.', [
            'source_host' => $this->sourceHost,
            'stage' => $errors[0] ?? 'unknown',
            'errors' => $errors,
        ]);

        return [
            'generation_status' => 'draft', 'generation_noindex' => true,
            'generation_review_required' => true, 'generation_errors' => $errors,
        ];
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
