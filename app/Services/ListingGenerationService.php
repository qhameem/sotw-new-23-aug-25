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
        $facts['platforms'] = $detected !== [] ? $detected : array_values((array) ($facts['platforms'] ?? []));
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

        foreach (['tagline', 'description', 'SEO'] as $stage) {
            if (isset($errors[$stage])) {
                $base['field_errors'][$stage === 'SEO' ? 'meta_description' : $stage] = implode(' ', $errors[$stage]);
            }
        }
        if ($errors !== []) {
            return array_merge($base, $this->draft(array_merge(...array_values($errors))));
        }
        $report->record('generation', 'Generation completed; AI claim verification is disabled.', 'success');
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

    private function classify(string $factsJson, string $source, array $detectedPlatforms, int $maxAttempts = 2): ?array
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
        $prompt = $this->ai->prompt('category_classification_prompt.txt', $bindings);
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $result = $this->ai->json($prompt);
            if (is_array($result) && isset($result['categories'], $result['use_cases'], $result['best_for'], $result['pricing'], $result['platforms'], $result['suggestions'])
                && is_array($result['suggestions']) && is_array($result['suggestions']['categories'] ?? null) && is_array($result['suggestions']['use_cases'] ?? null)) {
                $picks = [];
                foreach (['categories', 'use_cases', 'best_for', 'pricing', 'platforms'] as $key) {
                    if (! is_array($result[$key])) {
                        continue 2;
                    }
                    $picks[$key] = $this->canonicalPicks(self::classificationNames($result[$key]), $available[$key]);
                }
                foreach ($result['pricing'] as $pick) {
                    if (! is_array($pick)) {
                        continue;
                    }
                    $name = is_string($pick['name'] ?? null) ? $pick['name'] : 'unknown';
                    $quote = is_string($pick['quote'] ?? null) ? $pick['quote'] : '';
                    $accepted = in_array($name, self::classificationNames([$pick]), true)
                        && $this->canonicalPicks([$name], $available['pricing']) !== [];
                    \App\Support\ExtractionReport::current()->record('pricing classification evidence',
                        $name.': '.($accepted ? 'category accepted' : 'rejected; category unavailable').'; quote: '.json_encode($quote, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        $accepted ? 'info' : 'warning');
                }
                $picks['platforms'] = $this->canonicalPicks(array_merge($detectedPlatforms, $picks['platforms']), $available['platforms']);
                $ids = fn ($items) => Category::whereIn('name', $items)->pluck('id')->all();
                $fieldErrors = [];
                foreach (['categories' => 'categories', 'use_cases' => 'useCases', 'pricing' => 'pricing'] as $key => $field) {
                    if ($picks[$key] !== []) {
                        $fieldErrors[$field] = '';
                    } elseif ($available[$key] === []) {
                        $fieldErrors[$field] = 'No configured options are available. Ask an administrator to configure them.';
                    } elseif ($result[$key] === []) {
                        $fieldErrors[$field] = 'No options were returned. Select an option or add a custom suggestion.';
                    } else {
                        $fieldErrors[$field] = 'Returned options did not match the configured options. Select an option or add a custom suggestion.';
                    }
                }

                return [
                    'categories' => $ids($picks['categories']), 'useCases' => $ids($picks['use_cases']),
                    'bestFor' => $ids($picks['best_for']), 'pricing' => $ids($picks['pricing']),
                    'platforms' => $ids($picks['platforms']),
                    'field_errors' => $fieldErrors,
                    'suggestedCategories' => $picks['categories'] === [] ? (array) ($result['suggestions']['categories'] ?? []) : [],
                    'suggestedUseCases' => $picks['use_cases'] === [] ? (array) ($result['suggestions']['use_cases'] ?? []) : [],
                ];
            }
            \App\Support\ExtractionReport::current()->record('classification', 'Invalid classification schema; retrying.', 'warning');
            $prompt .= "\nValidation errors to fix:\nClassification JSON schema is invalid.";
            $this->lastErrors = array_merge(['Classification JSON schema is invalid.'], $result === null ? $this->ai->errors() : []);
        }

        return null;
    }

    public static function classificationNames(array $picks): array
    {
        $valid = [];
        foreach ($picks as $pick) {
            if (is_array($pick) && is_string($pick['name'] ?? null) && trim($pick['name']) !== '') {
                $valid[] = $pick['name'];
            }
        }

        return array_values(array_unique($valid));
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
