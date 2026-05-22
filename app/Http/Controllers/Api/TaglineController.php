<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function generate(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:5000',
            'url' => 'required|url',
        ]);

        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $request->input('url'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $apiKey = config('services.google.api_key');
        if (!$apiKey) {
            return $this->fallbackToMetadata($url);
        }

        try {
            $response = Http::withHeaders([
                'X-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent", [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => "Based on the product description \"{$request->input('description')}\", generate two product taglines in JSON format. Write like a human product copywriter, not an AI assistant. Both lines must be punchy, natural, and immediately explain what the product does. The 'short' tagline should ideally be 35-85 characters and never exceed 140. The 'detailed' tagline should ideally be 45-110 characters and never exceed 160. The detailed line can be slightly fuller, but it must still be a one-line explanation, not a mini paragraph. Avoid hype, slogans, rhetorical questions, ad-style openings like 'Meet...' or 'Your X shouldn't...'. Do not copy whole lines from the description, but if it contains a short, distinctive positioning phrase that is clearly the best explanation of the product, you may keep that phrase. Preserve strong hooks from the source when they are specific and useful, such as 'one-person company', instead of flattening them into generic terms like 'business', 'company', 'platform', or 'tool'. Prefer the source's clearest native terminology, so do not replace 'AI agents' with broader wording like 'AI team' unless the source clearly does that. Mention pricing, 'no subscription', 'free', or 'one-time purchase' only when the source clearly presents that as a meaningful differentiator or buyer reason to choose the product, especially in categories where recurring subscriptions are the norm. Return only the JSON object with keys 'short' and 'detailed'."
                                    ]
                                ]
                            ]
                        ]
                    ]);

            if ($response->failed()) {
                throw new \Exception('API request failed.');
            }

            $generatedText = $response->json('candidates.0.content.parts.0.text', '');
            $cleanedText = trim($generatedText);
            if (strpos($cleanedText, '```json') === 0) {
                $cleanedText = substr($cleanedText, 7, -3);
            }

            $taglines = json_decode($cleanedText, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($taglines['short']) || !isset($taglines['detailed'])) {
                throw new \Exception('Failed to parse JSON response.');
            }

            return response()->json([
                'tagline' => $this->normalizeGeneratedLine((string) $taglines['short'], self::TAGLINE_SOFT_MAX, self::TAGLINE_HARD_MAX),
                'tagline_detailed' => $this->normalizeGeneratedLine((string) $taglines['detailed'], self::TAGLINE_DETAILED_SOFT_MAX, self::TAGLINE_DETAILED_HARD_MAX),
            ]);

        } catch (\Exception $e) {
            Log::error('AI tagline generation failed, falling back to metadata.', ['error' => $e->getMessage()]);
            return $this->fallbackToMetadata($url);
        }
    }

    private function fallbackToMetadata(string $url)
    {
        try {
            $response = Http::get($url);
            $html = $response->body();

            $doc = new \DOMDocument();
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
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch metadata for tagline fallback.', ['url' => $url, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch metadata.'], 500);
        }
    }

    private function normalizeGeneratedLine(string $text, int $softMax, int $hardMax): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

        return trim(rtrim($text, " .!?,;:-"));
    }

    private function dropPromotionalLeadIn(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (!is_array($sentences) || count($sentences) < 2) {
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
