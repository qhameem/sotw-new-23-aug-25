<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Support\ProductDescriptionTemplates;

class DescriptionRewriterService
{
    public const UNKNOWN_LIMITATION = 'Not clearly stated in the available source material.';

    private const TIMEOUT = 60;

    private array $failures = [];

    private bool $usedFallback = false;

    /**
     * Rewrite raw product information into a concise overview-only HTML block.
     */
    public function rewrite(string $productName, string $rawDescription, string $pageTextContext = ''): ?string
    {
        $this->failures = [];
        $this->usedFallback = false;
        $productName = $this->normalizeProductName($productName);
        $providerRouter = app(AiProviderRoutingService::class);

        if (empty(trim($rawDescription))) {
            return null;
        }

        $context = mb_substr(strip_tags($pageTextContext), 0, 8000);
        $adminInstruction = app(ProductDescriptionTemplates::class)->activeInstruction();
        $prompt = $this->buildPrompt($productName, $rawDescription, $context, $adminInstruction);

        if ($providerRouter->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']) === []) {
            Log::warning('DescriptionRewriterService: No AI provider key is set.');
            $this->recordFailure('system', null, 'No AI provider key is set.');
            $this->usedFallback = true;

            return $this->buildFallbackHtml($productName, $rawDescription, $context);
        }

        try {
            foreach ($providerRouter->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']) as $candidate) {
                $response = match ($candidate['provider']) {
                    'groq' => $this->generateWithGroq($candidate['key'], $prompt),
                    'openrouter' => $this->generateWithOpenRouter($candidate['key'], $prompt),
                    default => $this->generateWithGemini($candidate['key'], $prompt),
                };

                if (! is_string($response) || trim($response) === '') {
                    continue;
                }

                $cleaned = $this->cleanHtmlResponse($response);

                if ($cleaned !== null) {
                    return $cleaned;
                }
            }
        } catch (\Exception $e) {
            Log::warning('DescriptionRewriterService: Exception', ['message' => $e->getMessage()]);
            $this->recordFailure('system', null, $e->getMessage());
            $this->usedFallback = true;

            return $this->buildFallbackHtml($productName, $rawDescription, $context);
        }

        $this->usedFallback = true;

        return $this->buildFallbackHtml($productName, $rawDescription, $context);
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function usedFallback(): bool
    {
        return $this->usedFallback;
    }

    private function buildPrompt(string $productName, string $rawDescription, string $context, ?string $adminInstruction = null): string
    {
        if (filled($adminInstruction)) {
            return $this->buildCustomPrompt($productName, $rawDescription, $context, $adminInstruction);
        }

        $prompt = <<<PROMPT
Write a concise overview for "{$productName}" using only the source material.

Raw information: "{$rawDescription}"
Additional context: "{$context}"

REQUIREMENTS:
- Return only one or two <p> paragraphs of clean HTML.
- Explain what the product is, what it does, and its main practical value.
- Mention "{$productName}" naturally in the first paragraph.
- Use plain, neutral English. Keep claims specific and source-supported.
- Do not add headings, lists, key features, best-for audiences, use cases, comparisons, integrations, pros, limitations, FAQs, or editorial notes.
- Do not return Markdown, labels, commentary, or code fences.
PROMPT;

        return $prompt;
    }

    private function buildCustomPrompt(string $productName, string $rawDescription, string $context, string $adminInstruction): string
    {
        return <<<PROMPT
Write or rewrite the product description for "{$productName}".

ADMIN INSTRUCTION:
{$adminInstruction}

SOURCE MATERIAL:
Raw information: "{$rawDescription}"
Additional context: "{$context}"

REQUIREMENTS:
- Use the admin instruction only for tone and overview length when it is compatible with the rules below.
- Return only one or two <p> overview paragraphs.
- Use only facts supported by the source material. Do not invent claims, limitations, integrations, audiences, or comparisons.
- Write naturally in plain English without hype or unsupported superlatives.
- Do not add headings, lists, key features, best-for audiences, use cases, comparisons, integrations, pros, limitations, FAQs, or editorial notes.
- Return only clean HTML using <p> and optional <strong> or <em> elements.
- Do not return Markdown, code fences, labels, or commentary.
PROMPT;
    }

    private function generateWithGemini(string $apiKey, string $prompt): ?string
    {
        $model = (string) config('services.google.gemini_model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('services.google.gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(self::TIMEOUT)->post($baseUrl.'/models/'.$model.':generateContent', [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);

        if ($response->successful()) {
            app(AiProviderRoutingService::class)->recordHttpSuccess('gemini', $response);
            $content = $response->json('candidates.0.content.parts.0.text');

            return is_string($content) ? $content : null;
        }

        Log::warning('DescriptionRewriterService: Gemini API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        app(AiProviderRoutingService::class)->recordHttpFailure('gemini', $response);
        $this->recordFailure('gemini', $response->status(), $response->body());

        return null;
    }

    private function generateWithGroq(string $apiKey, string $prompt): ?string
    {
        $baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');

        $response = Http::timeout(self::TIMEOUT)
            ->withToken($apiKey)
            ->post($baseUrl.'/chat/completions', [
                'model' => (string) config('services.groq.model', 'llama-3.3-70b-versatile'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.55,
                'max_completion_tokens' => 4000,
                'reasoning_format' => 'hidden',
            ]);

        if ($response->successful()) {
            app(AiProviderRoutingService::class)->recordHttpSuccess('groq', $response);
            $content = $response->json('choices.0.message.content');

            return is_string($content) ? $content : null;
        }

        Log::warning('DescriptionRewriterService: Groq API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        app(AiProviderRoutingService::class)->recordHttpFailure('groq', $response);
        $this->recordFailure('groq', $response->status(), $response->body());

        return null;
    }

    private function generateWithOpenRouter(string $apiKey, string $prompt): ?string
    {
        $baseUrl = rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ])
            ->post($baseUrl.'/chat/completions', [
                'model' => (string) config('services.openrouter.model', 'openrouter/free'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.55,
                'max_tokens' => 4000,
                'reasoning' => [
                    'exclude' => true,
                ],
            ]);

        if ($response->successful()) {
            app(AiProviderRoutingService::class)->recordHttpSuccess('openrouter', $response);
            $content = $response->json('choices.0.message.content');

            return is_string($content) ? $content : null;
        }

        Log::warning('DescriptionRewriterService: OpenRouter API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        app(AiProviderRoutingService::class)->recordHttpFailure('openrouter', $response);
        $this->recordFailure('openrouter', $response->status(), $response->body());

        return null;
    }

    private function recordFailure(string $provider, ?int $status, string $body): void
    {
        $this->failures[] = [
            'provider' => $provider,
            'status' => $status,
            'body' => $body,
        ];
    }

    private function cleanHtmlResponse(string $content): ?string
    {
        $content = trim($this->stripMarkdownFence($content));

        $firstHtmlPosition = $this->firstHtmlPosition($content);

        if ($firstHtmlPosition !== null && $firstHtmlPosition > 0) {
            $content = substr($content, $firstHtmlPosition);
        }

        if ($content === '') {
            return null;
        }

        if (! str_contains($content, '<p>')) {
            return null;
        }

        return $this->extractOverviewParagraphs($content);
    }

    private function extractOverviewParagraphs(string $content): ?string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?><div id="overview-root">'.$content.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (! $loaded) {
            return null;
        }

        $root = (new DOMXPath($document))->query("//*[@id='overview-root']")->item(0);

        if (! $root instanceof DOMElement) {
            return null;
        }

        $paragraphs = [];

        foreach ($root->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if (strtolower($node->tagName) !== 'p') {
                if ($paragraphs !== []) {
                    break;
                }

                continue;
            }

            if ($this->cleanPlainText($node->textContent) !== '') {
                $paragraphs[] = $document->saveHTML($node);
            }

            if (count($paragraphs) === 2) {
                break;
            }
        }

        return $paragraphs === [] ? null : implode("\n", $paragraphs);
    }

    private function firstHtmlPosition(string $content): ?int
    {
        $positions = array_filter([
            strpos($content, '<p>'),
            strpos($content, '<h2>'),
        ], static fn (int|false $position): bool => $position !== false);

        return $positions === [] ? null : min($positions);
    }

    private function hasRequiredLongFormSections(string $content): bool
    {
        $requiredFragments = [
            '<h2><strong>What is ',
            '<h2><strong>What are the key features of ',
            '<h2><strong>Who is ',
            '<h2><strong>What can you use ',
            '<h2><strong>How does ',
            '<h2><strong>What integrations and ecosystem support does ',
            '<h2><strong>Frequently asked questions about ',
            '<dl>',
        ];

        foreach ($requiredFragments as $fragment) {
            if (! str_contains($content, $fragment)) {
                return false;
            }
        }

        $hasProsSection = str_contains($content, '<h2><strong>What are the pros and limitations of ')
            || str_contains($content, '<h2><strong>What are the pros of ');

        if (! $hasProsSection) {
            return false;
        }

        return substr_count($content, '<li>') >= 10
            && substr_count($content, '<dt>') >= 2
            && substr_count($content, '<dd>') >= 2;
    }

    public static function isUnknownLimitationText(string $value): bool
    {
        $normalized = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? '';
        $normalized = trim(mb_strtolower($normalized), " \t\n\r\0\x0B.:;-");

        if ($normalized === '') {
            return true;
        }

        if ($normalized === mb_strtolower(self::UNKNOWN_LIMITATION)) {
            return true;
        }

        return (bool) preg_match('/\b(?:not|no)\b.*\b(?:clear|clearly|specific|specified|stated|known|available|mentioned)\b.*\b(?:limitation|limitations|source|source material|available source material|information|details)\b/u', $normalized)
            || (bool) preg_match('/\b(?:limitations?|drawbacks?)\b.*\b(?:unclear|unknown|unspecified|not available|not mentioned)\b/u', $normalized);
    }

    private function normalizeLimitationsSection(string $content): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<!DOCTYPE html><html><body><div id="editorial-root">'.$content.'</div></body></html>';

        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>'.$wrappedHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (! $loaded) {
            return $content;
        }

        $xpath = new DOMXPath($document);
        $root = $xpath->query("//*[@id='editorial-root']")->item(0);

        if (! $root instanceof DOMElement) {
            return $content;
        }

        foreach ($root->childNodes as $node) {
            if (! $node instanceof DOMElement || strtolower($node->tagName) !== 'h2') {
                continue;
            }

            if (! str_contains(Str::lower($this->cleanPlainText($node->textContent)), 'pros and limitations')) {
                continue;
            }

            $listNode = $this->nextElementSibling($node);

            if (! $listNode instanceof DOMElement || ! in_array(strtolower($listNode->tagName), ['ul', 'ol'], true)) {
                continue;
            }

            $hasSupportedLimitation = false;

            foreach (iterator_to_array($listNode->childNodes) as $childNode) {
                if (! $childNode instanceof DOMElement || strtolower($childNode->tagName) !== 'li') {
                    continue;
                }

                $itemText = $this->cleanPlainText($childNode->textContent);
                $normalizedText = Str::lower($itemText);

                if (! str_starts_with($normalizedText, 'limitations:')) {
                    continue;
                }

                $limitationText = trim(substr($itemText, strlen('Limitations:')));

                if (self::isUnknownLimitationText($itemText) || self::isUnknownLimitationText($limitationText)) {
                    $listNode->removeChild($childNode);

                    continue;
                }

                $hasSupportedLimitation = true;
            }

            if (! $hasSupportedLimitation) {
                $this->rewriteProsHeadingWithoutLimitations($node);
            }
        }

        return $this->renderRootChildren($document, $root);
    }

    private function buildFallbackHtml(string $productName, string $rawDescription, string $context): string
    {
        $summary = $this->buildFallbackSummary($productName, $rawDescription);
        $supporting = $this->buildFallbackSupportingSentence($rawDescription, $context);

        return implode("\n", [
            '<p><strong>'.e($summary).'</strong></p>',
            ...($supporting !== '' ? ['<p>'.e($supporting).'</p>'] : []),
        ]);
    }

    private function buildFallbackWhatIs(string $productName, string $summary, string $supporting, array $bodySentences): string
    {
        $lead = $summary;

        if ($productName !== 'this product' && ! Str::contains(Str::lower($lead), Str::lower($productName))) {
            $lead = $productName.' helps users '.Str::lcfirst(rtrim($lead, '.')).'.';
        }

        $lead = rtrim($lead, '. ').'.';

        $second = $bodySentences[0] ?? $supporting;
        $second = $this->ensureSentenceLength($second, 180);

        if ($second === '') {
            return $lead;
        }

        $second = rtrim($second, '. ').'.';

        if (Str::lower($second) === Str::lower($lead)) {
            $second = 'It is positioned around a practical workflow, with the available source material emphasizing how the product is used in day-to-day work.';
        }

        return $lead.' '.$second;
    }

    private function buildFallbackSummary(string $productName, string $rawDescription): string
    {
        $summary = $this->cleanPlainText($rawDescription);

        if ($summary === '') {
            return $productName.' helps people plan, build, and manage work more clearly.';
        }

        if ($productName !== 'this product' && ! Str::contains(Str::lower($summary), Str::lower($productName))) {
            $summary = preg_match('/^(?:a|an|the)\s+/i', $summary)
                ? $productName.' is '.Str::lcfirst(rtrim($summary, '.')).'.'
                : $productName.' helps you '.Str::lcfirst(rtrim($summary, '.')).'.';
        }

        return $this->ensureSentenceLength($summary, 260);
    }

    private function buildFallbackSupportingSentence(string $rawDescription, string $context): string
    {
        $sentences = $this->extractBodySentences($context);

        foreach ($sentences as $sentence) {
            if ($sentence !== '' && ! str_contains(Str::lower($sentence), Str::lower($this->cleanPlainText($rawDescription)))) {
                return $this->ensureSentenceLength($sentence, 220);
            }
        }

        return '';
    }

    private function buildFallbackFeatures(array $headingCandidates, array $bodySentences): array
    {
        $features = array_slice(array_values(array_unique(array_merge($headingCandidates, $bodySentences))), 0, 5);

        return array_slice($features, 0, 5);
    }

    private function buildFallbackIdealFor(string $context): array
    {
        $normalized = Str::lower($context);
        $candidates = [];

        $audienceMap = [
            'solo builders' => 'Solo builders who want faster execution with clearer project structure.',
            'startup teams' => 'Startup teams managing product delivery and iteration across multiple moving parts.',
            'agencies' => 'Agencies coordinating planning, delivery, and client-facing execution.',
            'founders' => 'Founders who need a tighter link between planning, delivery, and output.',
            'project managers' => 'Project managers who want more visibility into roadmap, capacity, and execution.',
            'designers' => 'Designers collaborating inside broader product or delivery workflows.',
            'enterprise' => 'Enterprise teams looking for more structured AI-assisted planning and execution.',
        ];

        foreach ($audienceMap as $needle => $line) {
            if (str_contains($normalized, $needle)) {
                $candidates[] = $line;
            }
        }

        return array_slice(array_values(array_unique($candidates)), 0, 3);
    }

    private function buildFallbackUseCases(array $headingCandidates, array $bodySentences): array
    {
        $candidates = [];

        foreach (array_merge($headingCandidates, $bodySentences) as $candidate) {
            if (preg_match('/\b(build|plan|track|manage|generate|execute|review|debug|ship|deploy|estimate|collaborate)\b/i', $candidate)) {
                $candidates[] = $candidate;
            }
        }

        return array_slice(array_values(array_unique($candidates)), 0, 3);
    }

    private function buildFallbackIntegrations(string $context): array
    {
        $matches = [];

        foreach (['Supabase', 'Stripe', 'GitHub', 'Firebase', 'Sentry', 'Intercom', 'PayPal', 'Firecrawl', 'n8n'] as $integration) {
            if (str_contains(Str::lower($context), Str::lower($integration))) {
                $matches[] = $integration;
            }
        }

        if ($matches === []) {
            return [];
        }

        return ['Integrations mentioned by the product include '.implode(', ', array_slice($matches, 0, 6)).'.'];
    }

    private function buildFallbackFaq(string $productName, string $summary, array $idealFor): array
    {
        $items = [
            [
                'question' => 'What does '.$productName.' help you do?',
                'answer' => $summary,
            ],
        ];

        if ($idealFor !== []) {
            $items[] = [
                'question' => 'Who is '.$productName.' best for?',
                'answer' => implode(' ', array_slice($idealFor, 0, 2)),
            ];
        }

        return $items;
    }

    private function extractHeadingCandidates(string $context): array
    {
        preg_match_all('/(?:^|\n)H[1-3]:\s*(.+)/u', $context, $matches);
        $headings = [];

        foreach ($matches[1] ?? [] as $heading) {
            $heading = $this->cleanPlainText($heading);

            if ($this->isWeakFallbackCandidate($heading)) {
                continue;
            }

            $headings[] = $this->ensureSentenceLength($heading, 160);
        }

        return array_slice(array_values(array_unique($headings)), 0, 8);
    }

    private function extractBodySentences(string $context): array
    {
        $parts = preg_split('/BODY CONTENT:\s*/u', $context, 2);
        $body = $parts[1] ?? '';

        if ($body === '') {
            return [];
        }

        $body = preg_split('/(?:ADDITIONAL RESOURCES:|LIMITATION RESEARCH:)\s*/u', $body, 2)[0] ?? $body;

        $sentences = preg_split('/(?<=[.!?])\s+/u', $body, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $cleaned = [];

        foreach ($sentences as $sentence) {
            $sentence = $this->cleanPlainText($sentence);

            if ($this->isWeakFallbackCandidate($sentence) || mb_strlen($sentence) < 35) {
                continue;
            }

            $cleaned[] = $this->ensureSentenceLength($sentence, 180);

            if (count($cleaned) >= 8) {
                break;
            }
        }

        return $cleaned;
    }

    private function isWeakFallbackCandidate(string $value): bool
    {
        $normalized = Str::lower(trim($value));

        if ($normalized === '' || str_word_count($normalized) < 2 || mb_strlen($normalized) < 12) {
            return true;
        }

        if (preg_match('/[$%]|^\d+$/', $normalized)) {
            return true;
        }

        if (preg_match('/\bnewmanage\b|claudeautomate|chatgpt and claudeautomate/u', $normalized)) {
            return true;
        }

        return in_array($normalized, [
            'platform',
            'agile',
            'community',
            'pricing',
            'legal',
            'solutions',
            'reports',
            'roadmap',
            'backlog',
            'dashboard',
            'board',
            'qa mode',
            'site wide links',
            'ready to build',
            'live preview',
            'start building',
            'get started',
            'try it free',
        ], true);
    }

    private function ensureSentenceLength(string $value, int $maxLength): string
    {
        $value = $this->cleanPlainText($value);

        if ($value === '') {
            return '';
        }

        return rtrim(Str::limit($value, $maxLength, '...'), " \t\n\r\0\x0B");
    }

    private function cleanPlainText(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return trim($value, " \t\n\r\0\x0B-");
    }

    private function renderList(array $items): string
    {
        $html = '<ul>';

        foreach ($items as $item) {
            $html .= '<li>'.e($item).'</li>';
        }

        return $html.'</ul>';
    }

    private function renderFaq(array $items): string
    {
        $html = '<dl>';

        foreach ($items as $item) {
            $html .= '<dt><strong>'.e($item['question']).'</strong></dt>';
            $html .= '<dd>'.e($item['answer']).'</dd>';
        }

        return $html.'</dl>';
    }

    private function stripMarkdownFence(string $content): string
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
        }

        return trim($trimmed);
    }

    private function nextElementSibling(DOMElement $node): ?DOMElement
    {
        $sibling = $node->nextSibling;

        while ($sibling !== null) {
            if ($sibling instanceof DOMElement) {
                return $sibling;
            }

            $sibling = $sibling->nextSibling;
        }

        return null;
    }

    private function rewriteProsHeadingWithoutLimitations(DOMElement $headingNode): void
    {
        $updatedHeading = preg_replace(
            '/What are the pros and limitations of\s+/iu',
            'What are the pros of ',
            $this->cleanPlainText($headingNode->textContent)
        );

        if (! is_string($updatedHeading) || $updatedHeading === '') {
            return;
        }

        foreach ($headingNode->childNodes as $childNode) {
            if ($childNode instanceof DOMElement && strtolower($childNode->tagName) === 'strong') {
                $childNode->nodeValue = $updatedHeading;

                return;
            }
        }

        $headingNode->nodeValue = $updatedHeading;
    }

    private function renderRootChildren(DOMDocument $document, DOMElement $root): string
    {
        $html = '';

        foreach ($root->childNodes as $childNode) {
            $html .= $document->saveHTML($childNode);
        }

        return trim($html);
    }

    private function normalizeProductName(string $productName): string
    {
        $productName = trim($productName);

        if ($productName === '') {
            return 'this product';
        }

        return $productName;
    }

    private function escapeForPrompt(string $value): string
    {
        return str_replace('"', '\"', $value);
    }
}
