<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DescriptionRewriterService
{
    public const UNKNOWN_LIMITATION = 'Not clearly stated in the available source material.';

    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    private const TIMEOUT = 60;
    private array $failures = [];
    private bool $usedFallback = false;

    /**
     * Rewrite a raw product description into a long-form editorial HTML block.
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
        $prompt = $this->buildPrompt($productName, $rawDescription, $context);

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

                if (!is_string($response) || trim($response) === '') {
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

    private function buildPrompt(string $productName, string $rawDescription, string $context): string
    {
        return <<<PROMPT
You are an experienced human writer with 20+ years of experience. Write naturally, clearly, and convincingly. Your job is to write or rewrite the product description for "{$productName}" so it feels genuinely human-written, useful, easy to trust, and easy for AI search engines to extract accurately.

Raw information: "{$rawDescription}"

Additional context: "{$context}"

OBJECTIVE:
- Make the description feel complete, useful, and editorial rather than compressed.
- Explain what the product does, why it matters, who it helps, and how it fits into a workflow.
- Write enough detail to produce a rich long-form description with multiple clear sections.
- Make the page easy for AI systems to cite when a user asks a narrow, real-world question.
- Lead with the concrete task, problem, or outcome the product solves instead of broad category language.
- Keep claims grounded in the source material. If some details are unclear, stay conservative instead of inventing facts.

HUMAN WRITING RULES:
- Write like a real person explaining a product to another person.
- Use plain English, natural phrasing, and varied sentence lengths.
- Avoid robotic filler, generic hype, and repeated claims.
- Avoid clichés like "game-changing", "revolutionary", "cutting-edge", or "seamless".
- Avoid unsupported superlatives like "best", "#1", "leading", "most popular", or "world-class" unless the source explicitly supports them and they are central to understanding the product.
- Prefer specific, checkable details such as supported platforms, file types, integrations, workflows, pricing hooks, or audience fit when the source provides them.
- If the source is vague, rewrite it into plain, concrete language or leave it out.
- Every section should include product-specific information that would sound wrong if pasted onto another tool.
- If a section has limited source support, keep it brief but still useful.

AEO / STRUCTURE RULES:
- Preserve the exact HTML structure, section order, headings, and list types shown below.
- Do not add, remove, rename, merge, or reorder sections.
- Return ONLY HTML. No markdown fences, no labels, no explanations.
- Keep the first two lines as exactly two separate <p> paragraphs.
- Mention "{$productName}" naturally in the opening paragraph.
- Make the opening read like a direct answer to a likely user query.
- Front-load the most concrete nouns and verbs. If the source supports them, mention the object, platform, file type, workflow, or audience in the first two paragraphs.
- Keep each bullet concise, concrete, and focused on user value.
- Write bullets so each one can stand on its own as a quotable fact.
- Use question-based headings so the description reads more like direct answers than a generic editorial page.
- Favor narrow, user-phrased questions in the FAQ, especially about platform support, setup, pricing, workflows, limitations, inputs/outputs, and who the product is best for.
- Write grounded FAQ questions and answers instead of generic filler.
- If limitations are unclear even after reviewing the full context, do not mention them.
- Never write placeholder copy about missing, unavailable, or unclear limitations.

HTML STRUCTURE TO FOLLOW EXACTLY:
<p><strong>[Write a 40-70 word opening paragraph that clearly explains what {$productName} is, who it helps, and why someone would choose it.]</strong></p>
<p>[Write a second paragraph that expands on the main workflow, differentiator, or practical use without hype.]</p>

<h2><strong>What is {$productName}?</strong></h2>
<p>[Write 2 short plain-English sentences that explain what the product does and how it fits into a user's workflow.]</p>

<h2><strong>What are the key features of {$productName}?</strong></h2>
<ul>
  <li>[Feature 1 focused on user value]</li>
  <li>[Feature 2 focused on workflow or product capability]</li>
  <li>[Feature 3 focused on a distinct benefit]</li>
  <li>[Feature 4 if supported by the source]</li>
  <li>[Feature 5 if supported by the source]</li>
</ul>

<h2><strong>Who is {$productName} best for?</strong></h2>
<ul>
  <li>[Specific audience 1]</li>
  <li>[Specific audience 2]</li>
  <li>[Specific audience 3 if supported]</li>
</ul>

<h2><strong>What can you use {$productName} for?</strong></h2>
<ul>
  <li>[Concrete use case 1]</li>
  <li>[Concrete use case 2]</li>
  <li>[Concrete use case 3]</li>
</ul>

<h2><strong>How does {$productName} compare to alternatives?</strong></h2>
<ul>
  <li>[Alternative 1 with a grounded comparison]</li>
  <li>[Alternative 2 with a grounded comparison]</li>
</ul>

<h2><strong>What integrations and ecosystem support does {$productName} offer?</strong></h2>
<ul>
  <li>[Specific integrations, APIs, platforms, or ecosystem details from the source. If unclear, say that the available source material does not clearly specify integrations.]</li>
</ul>

<h2><strong>What are the pros and limitations of {$productName}?</strong></h2>
<ul>
  <li><strong>Pros:</strong> [List 2-3 grounded strengths separated by semicolons]</li>
  <li><strong>Limitations:</strong> [List 1-2 honest limitations based only on supported facts. Leave this blank if no clear limitation is supported.]</li>
</ul>

<h2><strong>Frequently asked questions about {$productName}</strong></h2>
<dl>
  <dt><strong>[Question 1 written like a real user search]</strong></dt>
  <dd>[Direct answer based only on supported facts.]</dd>
  <dt><strong>[Question 2 written like a real user search]</strong></dt>
  <dd>[Direct answer based only on supported facts.]</dd>
</dl>

STYLE CHECK BEFORE YOU RESPOND:
- Does it sound human, plainspoken, and useful?
- Is the structure exactly preserved?
- Are the claims grounded in the provided information?
- Does it feel fuller and more informative than a short summary?
- Do the FAQ items sound like real questions and not filler?
PROMPT;
    }

    private function generateWithGemini(string $apiKey, string $prompt): ?string
    {
        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(self::TIMEOUT)->post(self::GEMINI_API_URL, [
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
        $response = Http::timeout(self::TIMEOUT)
            ->withToken($apiKey)
            ->post(self::GROQ_API_URL, [
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.55,
                'max_tokens' => 1800,
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
        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ])
            ->post(self::OPENROUTER_API_URL, [
                'model' => (string) config('services.openrouter.model', 'openrouter/auto'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.55,
                'max_tokens' => 1800,
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

        if ($content === '') {
            return null;
        }

        if (!str_contains($content, '<p>') && !str_contains($content, '<h2>')) {
            return null;
        }

        if (!$this->hasRequiredLongFormSections($content)) {
            return null;
        }

        return $this->normalizeLimitationsSection($content);
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
            if (!str_contains($content, $fragment)) {
                return false;
            }
        }

        $hasProsSection = str_contains($content, '<h2><strong>What are the pros and limitations of ')
            || str_contains($content, '<h2><strong>What are the pros of ');

        if (!$hasProsSection) {
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
        $wrappedHtml = '<!DOCTYPE html><html><body><div id="editorial-root">' . $content . '</div></body></html>';

        libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrappedHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if (!$loaded) {
            return $content;
        }

        $xpath = new DOMXPath($document);
        $root = $xpath->query("//*[@id='editorial-root']")->item(0);

        if (!$root instanceof DOMElement) {
            return $content;
        }

        foreach ($root->childNodes as $node) {
            if (!$node instanceof DOMElement || strtolower($node->tagName) !== 'h2') {
                continue;
            }

            if (!str_contains(Str::lower($this->cleanPlainText($node->textContent)), 'pros and limitations')) {
                continue;
            }

            $listNode = $this->nextElementSibling($node);

            if (!$listNode instanceof DOMElement || !in_array(strtolower($listNode->tagName), ['ul', 'ol'], true)) {
                continue;
            }

            $hasSupportedLimitation = false;

            foreach (iterator_to_array($listNode->childNodes) as $childNode) {
                if (!$childNode instanceof DOMElement || strtolower($childNode->tagName) !== 'li') {
                    continue;
                }

                $itemText = $this->cleanPlainText($childNode->textContent);
                $normalizedText = Str::lower($itemText);

                if (!str_starts_with($normalizedText, 'limitations:')) {
                    continue;
                }

                $limitationText = trim(substr($itemText, strlen('Limitations:')));

                if (self::isUnknownLimitationText($itemText) || self::isUnknownLimitationText($limitationText)) {
                    $listNode->removeChild($childNode);
                    continue;
                }

                $hasSupportedLimitation = true;
            }

            if (!$hasSupportedLimitation) {
                $this->rewriteProsHeadingWithoutLimitations($node);
            }
        }

        return $this->renderRootChildren($document, $root);
    }

    private function buildFallbackHtml(string $productName, string $rawDescription, string $context): string
    {
        $summary = $this->buildFallbackSummary($productName, $rawDescription);
        $supporting = $this->buildFallbackSupportingSentence($rawDescription, $context);
        $headingCandidates = $this->extractHeadingCandidates($context);
        $bodySentences = $this->extractBodySentences($context);
        $whatIs = $this->buildFallbackWhatIs($productName, $summary, $supporting, $bodySentences);
        $features = $this->buildFallbackFeatures($headingCandidates, $bodySentences);
        $idealFor = $this->buildFallbackIdealFor($context);
        $useCases = $this->buildFallbackUseCases($headingCandidates, $bodySentences);
        $alternatives = [
            'The available source material focuses on this product\'s own workflow rather than direct competitor comparisons.',
            'Use the product\'s planning, building, and execution flow as the main point of comparison.',
        ];
        $integrations = [$this->buildFallbackIntegrationLine($context)];
        $prosLine = implode('; ', array_slice($features, 0, 3));
        $faq = $this->buildFallbackFaq($productName, $summary, $idealFor);

        return implode("\n", [
            '<p><strong>' . e($summary) . '</strong></p>',
            '<p>' . e($supporting) . '</p>',
            '<h2><strong>What is ' . e($productName) . '?</strong></h2>',
            '<p>' . e($whatIs) . '</p>',
            '<h2><strong>What are the key features of ' . e($productName) . '?</strong></h2>',
            $this->renderList($features),
            '<h2><strong>Who is ' . e($productName) . ' best for?</strong></h2>',
            $this->renderList($idealFor),
            '<h2><strong>What can you use ' . e($productName) . ' for?</strong></h2>',
            $this->renderList($useCases),
            '<h2><strong>How does ' . e($productName) . ' compare to alternatives?</strong></h2>',
            $this->renderList($alternatives),
            '<h2><strong>What integrations and ecosystem support does ' . e($productName) . ' offer?</strong></h2>',
            $this->renderList($integrations),
            '<h2><strong>What are the pros of ' . e($productName) . '?</strong></h2>',
            '<ul><li><strong>Pros:</strong> ' . e($prosLine) . '</li></ul>',
            '<h2><strong>Frequently asked questions about ' . e($productName) . '</strong></h2>',
            $this->renderFaq($faq),
        ]);
    }

    private function buildFallbackWhatIs(string $productName, string $summary, string $supporting, array $bodySentences): string
    {
        $lead = $summary;

        if ($productName !== 'this product' && !Str::contains(Str::lower($lead), Str::lower($productName))) {
            $lead = $productName . ' helps users ' . Str::lcfirst(rtrim($lead, '.')) . '.';
        }

        $lead = rtrim($lead, '. ') . '.';

        $second = $bodySentences[0] ?? $supporting;
        $second = $this->ensureSentenceLength($second, 180);
        $second = rtrim($second, '. ') . '.';

        if (Str::lower($second) === Str::lower($lead)) {
            $second = 'It is positioned around a practical workflow, with the available source material emphasizing how the product is used in day-to-day work.';
        }

        return $lead . ' ' . $second;
    }

    private function buildFallbackSummary(string $productName, string $rawDescription): string
    {
        $summary = $this->cleanPlainText($rawDescription);

        if ($summary === '') {
            return $productName . ' helps people plan, build, and manage work more clearly.';
        }

        if ($productName !== 'this product' && !Str::contains(Str::lower($summary), Str::lower($productName))) {
            $summary = $productName . ' helps users ' . Str::lcfirst(rtrim($summary, '.')) . '.';
        }

        return $this->ensureSentenceLength($summary, 260);
    }

    private function buildFallbackSupportingSentence(string $rawDescription, string $context): string
    {
        $sentences = $this->extractBodySentences($context);

        foreach ($sentences as $sentence) {
            if ($sentence !== '' && !str_contains(Str::lower($sentence), Str::lower($this->cleanPlainText($rawDescription)))) {
                return $this->ensureSentenceLength($sentence, 220);
            }
        }

        return 'The available source material highlights the core workflow and positioning, even when deeper editorial details are limited.';
    }

    private function buildFallbackFeatures(array $headingCandidates, array $bodySentences): array
    {
        $features = array_slice(array_values(array_unique(array_merge($headingCandidates, $bodySentences))), 0, 5);

        while (count($features) < 5) {
            $features[] = match (count($features)) {
                0 => 'Supports a structured workflow instead of leaving each step to ad hoc prompting.',
                1 => 'Keeps core project information in one place so planning and execution stay connected.',
                2 => 'Helps teams move from ideas to delivery with clearer context and less repetition.',
                3 => 'Surfaces practical planning or execution steps instead of abstract product messaging.',
                default => 'Turns the available source material into a more actionable overview of how the product works.',
            };
        }

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

        if ($candidates === []) {
            $candidates = [
                'Builders who want more structure around AI-assisted product work.',
                'Teams planning and executing software projects with shared context.',
                'Operators who need planning, execution, and tracking to stay connected.',
            ];
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

        if ($candidates === []) {
            $candidates = [
                'Planning software work with clearer tasks, scope, and delivery context.',
                'Executing multi-step project work without losing track of earlier decisions.',
                'Tracking delivery progress while keeping planning and implementation aligned.',
            ];
        }

        return array_slice(array_values(array_unique($candidates)), 0, 3);
    }

    private function buildFallbackIntegrationLine(string $context): string
    {
        $matches = [];

        foreach (['Supabase', 'Stripe', 'GitHub', 'Firebase', 'Sentry', 'Intercom', 'PayPal', 'Firecrawl', 'n8n'] as $integration) {
            if (str_contains(Str::lower($context), Str::lower($integration))) {
                $matches[] = $integration;
            }
        }

        if ($matches === []) {
            return 'The available source material does not clearly specify integrations or ecosystem details.';
        }

        return 'The source material references ecosystem or integration signals including ' . implode(', ', array_slice($matches, 0, 6)) . '.';
    }

    private function buildFallbackFaq(string $productName, string $summary, array $idealFor): array
    {
        return [
            [
                'question' => 'What does ' . $productName . ' help you do?',
                'answer' => $summary,
            ],
            [
                'question' => 'Who is ' . $productName . ' best for?',
                'answer' => implode(' ', array_slice($idealFor, 0, 2)),
            ],
        ];
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
            $html .= '<li>' . e($item) . '</li>';
        }

        return $html . '</ul>';
    }

    private function renderFaq(array $items): string
    {
        $html = '<dl>';

        foreach ($items as $item) {
            $html .= '<dt><strong>' . e($item['question']) . '</strong></dt>';
            $html .= '<dd>' . e($item['answer']) . '</dd>';
        }

        return $html . '</dl>';
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

        if (!is_string($updatedHeading) || $updatedHeading === '') {
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
