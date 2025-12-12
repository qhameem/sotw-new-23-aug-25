<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaglineController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:5000',
            'url' => 'required|url',
        ]);

        $apiKey = config('services.google.api_key');
        if (!$apiKey) {
            return $this->fallbackToMetadata($request->input('url'));
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
                                'text' => "Based on the product description \"{$request->input('description')}\", generate two taglines in JSON format: a 'short' one (max 60 chars, Product Hunt style) and a 'detailed' one (max 120 chars). Return only the JSON object with keys 'short' and 'detailed'."
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
                'tagline' => $taglines['short'],
                'tagline_detailed' => $taglines['detailed']
            ]);

        } catch (\Exception $e) {
            Log::error('AI tagline generation failed, falling back to metadata.', ['error' => $e->getMessage()]);
            return $this->fallbackToMetadata($request->input('url'));
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
                'tagline' => Str::limit(trim($title), 60),
                'tagline_detailed' => Str::limit(trim($description), 120)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch metadata for tagline fallback.', ['url' => $url, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch metadata.'], 500);
        }
    }
}
