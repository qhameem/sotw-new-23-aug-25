<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DescriptionRewriterService
{
    public const UNKNOWN_LIMITATION = 'Not clearly stated in the available source material.';

    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
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
        $googleApiKey = config('services.google.api_key');
        $groqApiKey = config('services.groq.key');

        if (empty(trim($rawDescription))) {
            return null;
        }

        $context = mb_substr(strip_tags($pageTextContext), 0, 8000);
        $prompt = $this->buildPrompt($productName, $rawDescription, $context);

        if (empty($googleApiKey) && empty($groqApiKey)) {
            Log::warning('DescriptionRewriterService: No AI provider key is set.');
            $this->recordFailure('system', null, 'No AI provider key is set.');
            $this->usedFallback = true;
            return $this->buildFallbackHtml($productName, $rawDescription, $context);
        }

        try {
            // Restore the old long-form behavior by preferring the Groq path that originally powered it.
            foreach ([
                ['provider' => 'groq', 'key' => $groqApiKey],
                ['provider' => 'gemini', 'key' => $googleApiKey],
            ] as $candidate) {
                if (empty($candidate['key'])) {
                    continue;
                }

                $response = $candidate['provider'] === 'groq'
                    ? $this->generateWithGroq($candidate['key'], $prompt)
                    : $this->generateWithGemini($candidate['key'], $prompt);

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
- Keep claims grounded in the source material. If some details are unclear, stay conservative instead of inventing facts.

HUMAN WRITING RULES:
- Write like a real person explaining a product to another person.
- Use plain English, natural phrasing, and varied sentence lengths.
- Avoid robotic filler, generic hype, and repeated claims.
- Avoid clichés like "game-changing", "revolutionary", "cutting-edge", or "seamless".
- Every section should include product-specific information that would sound wrong if pasted onto another tool.
- If a section has limited source support, keep it brief but still useful.

AEO / STRUCTURE RULES:
- Preserve the exact HTML structure, section order, headings, and list types shown below.
- Do not add, remove, rename, merge, or reorder sections.
- Return ONLY HTML. No markdown fences, no labels, no explanations.
- Keep the first two lines as exactly two separate <p> paragraphs.
- Mention "{$productName}" naturally in the opening paragraph.
- Keep each bullet concise, concrete, and focused on user value.
- Write grounded FAQ questions and answers instead of generic filler.
- If limitations are unclear, write exactly: "{$this->escapeForPrompt(self::UNKNOWN_LIMITATION)}"

HTML STRUCTURE TO FOLLOW EXACTLY:
<p><strong>[Write a 40-70 word opening paragraph that clearly explains what {$productName} is, who it helps, and why someone would choose it.]</strong></p>
<p>[Write a second paragraph that expands on the main workflow, differentiator, or practical use without hype.]</p>

<h2><strong>Key Features</strong></h2>
<ul>
  <li>[Feature 1 focused on user value]</li>
  <li>[Feature 2 focused on workflow or product capability]</li>
  <li>[Feature 3 focused on a distinct benefit]</li>
  <li>[Feature 4 if supported by the source]</li>
  <li>[Feature 5 if supported by the source]</li>
</ul>

<h2><strong>Ideal For</strong></h2>
<ul>
  <li>[Specific audience 1]</li>
  <li>[Specific audience 2]</li>
  <li>[Specific audience 3 if supported]</li>
</ul>

<h2><strong>Top Use Cases</strong></h2>
<ul>
  <li>[Concrete use case 1]</li>
  <li>[Concrete use case 2]</li>
  <li>[Concrete use case 3]</li>
</ul>

<h2><strong>Known Alternatives</strong></h2>
<ul>
  <li>[Alternative 1 with a grounded comparison]</li>
  <li>[Alternative 2 with a grounded comparison]</li>
</ul>

<h2><strong>Integrations &amp; Ecosystem</strong></h2>
<ul>
  <li>[Specific integrations, APIs, platforms, or ecosystem details from the source. If unclear, say that the available source material does not clearly specify integrations.]</li>
</ul>

<h2><strong>Pros &amp; Cons</strong></h2>
<ul>
  <li><strong>Pros:</strong> [List 2-3 grounded strengths separated by semicolons]</li>
  <li><strong>Limitations:</strong> [List 1-2 honest limitations based only on supported facts, or "{$this->escapeForPrompt(self::UNKNOWN_LIMITATION)}"]</li>
</ul>

<h2><strong>Frequently Asked Questions</strong></h2>
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
            $content = $response->json('candidates.0.content.parts.0.text');

            return is_string($content) ? $content : null;
        }

        Log::warning('DescriptionRewriterService: Gemini API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
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
            $content = $response->json('choices.0.message.content');

            return is_string($content) ? $content : null;
        }

        Log::warning('DescriptionRewriterService: Groq API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        $this->recordFailure('groq', $response->status(), $response->body());

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

        return $content;
    }

    private function hasRequiredLongFormSections(string $content): bool
    {
        $requiredFragments = [
            '<h2><strong>Key Features</strong></h2>',
            '<h2><strong>Ideal For</strong></h2>',
            '<h2><strong>Top Use Cases</strong></h2>',
            '<h2><strong>Known Alternatives</strong></h2>',
            '<h2><strong>Integrations',
            '<h2><strong>Pros',
            '<h2><strong>Frequently Asked Questions</strong></h2>',
            '<dl>',
        ];

        foreach ($requiredFragments as $fragment) {
            if (!str_contains($content, $fragment)) {
                return false;
            }
        }

        return substr_count($content, '<li>') >= 10
            && substr_count($content, '<dt>') >= 2
            && substr_count($content, '<dd>') >= 2;
    }

    private function buildFallbackHtml(string $productName, string $rawDescription, string $context): string
    {
        $summary = $this->buildFallbackSummary($productName, $rawDescription);
        $supporting = $this->buildFallbackSupportingSentence($rawDescription, $context);
        $headingCandidates = $this->extractHeadingCandidates($context);
        $bodySentences = $this->extractBodySentences($context);
        $features = $this->buildFallbackFeatures($headingCandidates, $bodySentences);
        $idealFor = $this->buildFallbackIdealFor($context);
        $useCases = $this->buildFallbackUseCases($headingCandidates, $bodySentences);
        $alternatives = [
            'The available source material focuses on this product\'s own workflow rather than direct competitor comparisons.',
            'Use the product\'s planning, building, and execution flow as the main point of comparison.',
        ];
        $integrations = [$this->buildFallbackIntegrationLine($context)];
        $prosLine = implode('; ', array_slice($features, 0, 3));
        $limitationsLine = self::UNKNOWN_LIMITATION;
        $faq = $this->buildFallbackFaq($productName, $summary, $idealFor);

        return implode("\n", [
            '<p><strong>' . e($summary) . '</strong></p>',
            '<p>' . e($supporting) . '</p>',
            '<h2><strong>Key Features</strong></h2>',
            $this->renderList($features),
            '<h2><strong>Ideal For</strong></h2>',
            $this->renderList($idealFor),
            '<h2><strong>Top Use Cases</strong></h2>',
            $this->renderList($useCases),
            '<h2><strong>Known Alternatives</strong></h2>',
            $this->renderList($alternatives),
            '<h2><strong>Integrations &amp; Ecosystem</strong></h2>',
            $this->renderList($integrations),
            '<h2><strong>Pros &amp; Cons</strong></h2>',
            '<ul><li><strong>Pros:</strong> ' . e($prosLine) . '</li><li><strong>Limitations:</strong> ' . e($limitationsLine) . '</li></ul>',
            '<h2><strong>Frequently Asked Questions</strong></h2>',
            $this->renderFaq($faq),
        ]);
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
