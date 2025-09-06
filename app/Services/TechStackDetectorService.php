<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechStackDetectorService
{
    public function detect(string $url): array
    {
        $apiKey = config('services.builtwith.api_key');

        if ($apiKey) {
            try {
                $response = Http::get('https://api.builtwith.com/free1/api.json', [
                    'KEY' => $apiKey,
                    'LOOKUP' => $url,
                ]);

                if ($response->successful()) {
                    $results = $response->json();
                    Log::info('BuiltWith API Response:', ['url' => $url, 'response' => $results]);
                    $detected = [];

                    if (isset($results['groups'])) {
                        foreach ($results['groups'] as $group) {
                            if (isset($group['name'])) {
                                $detected[] = $group['name'];
                            }
                        }
                    }
                    return array_unique($detected);
                }
            } catch (\Exception $e) {
                Log::error("Failed to detect tech stack for {$url} with BuiltWith: " . $e->getMessage());
            }
        }

        // Fallback to Wappalyzer
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.wappalyzer.api_key')
            ])->get('https://api.wappalyzer.com/v2/lookup/', [
                'urls' => $url,
            ]);

            if ($response->successful()) {
                $results = $response->json();
                Log::info('Wappalyzer API Response:', ['url' => $url, 'response' => $results]);
                $detected = [];
                if (!empty($results[0]['technologies'])) {
                    foreach ($results[0]['technologies'] as $tech) {
                        $detected[] = $tech['name'];
                    }
                }
                return array_unique($detected);
            }
        } catch (\Exception $e) {
            Log::error("Failed to detect tech stack for {$url} with Wappalyzer: " . $e->getMessage());
        }

        return [];
    }
}