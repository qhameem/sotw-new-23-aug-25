<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiProviderRoutingService;
use App\Services\ListingAiClient;
use App\Services\ProductRegenerationLimiter;
use App\Support\PublicUrlGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaglineController extends Controller
{
    private const TAGLINE_SOFT_MAX = 88;

    private const TAGLINE_HARD_MAX = 140;

    private const TAGLINE_DETAILED_SOFT_MAX = 120;

    private const TAGLINE_DETAILED_HARD_MAX = 160;

    public function generate(Request $request, ProductRegenerationLimiter $limiter, ListingAiClient $client, AiProviderRoutingService $router)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:5000',
            'url' => 'required|url',
            'draft_uuid' => 'required|string|size:36',
            'provider' => 'nullable|string|in:gemini,groq,cloudflare,openrouter,cerebras',
        ]);
        $provider = $validated['provider'] ?? null;
        if ($provider && (! $request->user() || ! $request->user()->is_admin())) {
            abort(403, 'Only administrators may choose an AI model.');
        }
        $regeneration = $limiter->consume($request->user(), $validated['draft_uuid'], 'tagline');

        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $request->input('url'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $punctuationInstruction = \App\Support\GeneratedCopy::PUNCTUATION_INSTRUCTION;

        try {
            $prompt = $punctuationInstruction."\nBased on the product description \"{$validated['description']}\", generate two product taglines. Write clear, factual, human directory copy without hype. The short line should be 35-85 characters and never exceed 140. The detailed line should be 45-110 characters and never exceed 160. Preserve specific product terminology and supported pricing differentiators. Return JSON only with keys short and detailed.";
            $taglines = $provider
                ? $client->jsonWithProvider($prompt, $provider, 800, 'copy')
                : $client->jsonWithProvider($prompt, 'gemini', 800, 'copy');

            if (! is_array($taglines) || ! isset($taglines['short'], $taglines['detailed'])) {
                throw new \Exception('Failed to parse JSON response.');
            }

            $usedProvider = $client->lastProvider();

            return response()->json([
                'tagline' => $this->normalizeGeneratedLine((string) $taglines['short'], self::TAGLINE_SOFT_MAX, self::TAGLINE_HARD_MAX),
                'tagline_detailed' => $this->normalizeGeneratedLine((string) $taglines['detailed'], self::TAGLINE_DETAILED_SOFT_MAX, self::TAGLINE_DETAILED_HARD_MAX),
                'regeneration' => $regeneration,
                'ai_provider' => $usedProvider,
                'ai_provider_label' => $usedProvider ? $router->providerLabel($usedProvider) : null,
                'ai_model' => $usedProvider ? $router->modelFor($usedProvider) : null,
            ]);

        } catch (\Exception $e) {
            Log::error('AI tagline generation failed, falling back to metadata.', ['error' => $e->getMessage()]);

            return $provider
                ? response()->json(['error' => 'The selected AI model could not generate a tagline.'], 422)
                : $this->fallbackToMetadata($url, $regeneration);
        }
    }

    private function fallbackToMetadata(string $url, array $regeneration)
    {
        try {
            $response = Http::get($url);
            $html = $response->body();

            $doc = new \DOMDocument;
            @$doc->loadHTML($html);

            $titleNode = $doc->getElementsByTagName('title')->item(0);
            $title = $titleNode ? $titleNode->nodeValue : '';

            $description = '';
            $metas = $doc->getElementsByTagName('meta');
            for ($i = 0; $i < $metas->length; $i++) {
                $meta = $metas->item($i);
                if (strtolower($meta->getAttribute('name')) == 'description') {
                    $description = $meta->getAttribute('content');
                }
            }

            return response()->json([
                'tagline' => $this->normalizeGeneratedLine(trim($title), self::TAGLINE_SOFT_MAX, self::TAGLINE_HARD_MAX),
                'tagline_detailed' => $this->normalizeGeneratedLine(trim($description), self::TAGLINE_DETAILED_SOFT_MAX, self::TAGLINE_DETAILED_HARD_MAX),
                'regeneration' => $regeneration,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch metadata for tagline fallback.', ['url' => $url, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to fetch metadata.'], 500);
        }
    }

    private function normalizeGeneratedLine(string $text, int $softMax, int $hardMax): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = \App\Support\GeneratedCopy::withoutEmDashes($text);
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        $text = trim($text, " \t\n\r\0\x0B\"'`");

        if ($text === '') {
            return '';
        }

        $text = $this->dropPromotionalLeadIn($text);

        if (mb_strlen($text) > $softMax) {
            $text = Str::limit($text, $softMax, '...');
        } elseif (mb_strlen($text) > $hardMax) {
            $text = Str::limit($text, $hardMax, '...');
        }

        return trim(rtrim($text, ' .!?,;:-'));
    }

    private function dropPromotionalLeadIn(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($sentences) || count($sentences) < 2) {
            return $text;
        }

        $firstSentence = mb_strtolower(trim($sentences[0]));
        $leadIns = [
            'meet ',
            'say hello',
            'introducing ',
            'finally',
            'your ',
            'stop ',
            'forget ',
            'no more ',
            'why ',
            'tired of ',
        ];

        foreach ($leadIns as $leadIn) {
            if (str_starts_with($firstSentence, $leadIn)) {
                return trim(implode(' ', array_slice($sentences, 1)));
            }
        }

        return $text;
    }
}
