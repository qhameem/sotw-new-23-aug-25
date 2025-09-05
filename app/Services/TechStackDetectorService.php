<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechStackDetectorService
{
    public function detect(string $url): array
    {
        $apiKey = config('services.builtwith.api_key');

        if (!$apiKey) {
            Log::error('BuiltWith API key is not configured.');
            return [];
        }

        try {
            $response = Http::get('https://api.builtwith.com/free1/api.json', [
                'KEY' => $apiKey,
                'LOOKUP' => $url,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch tech stack from BuiltWith', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return [];
            }

            $results = $response->json();
            Log::info('BuiltWith API Response:', ['url' => $url, 'response' => $results]);
            $detected = [];

            if (!empty($results['response']['groups'])) {
                foreach ($results['response']['groups'] as $group) {
                    if (isset($group['name'])) {
                        $detected[] = $group['name'];
                    }
                    if (!empty($group['categories'])) {
                        foreach ($group['categories'] as $category) {
                            if (isset($category['name'])) {
                                $detected[] = $category['name'];
                            }
                        }
                    }
                }
            }

            return array_unique($detected);
        } catch (\Exception $e) {
            Log::error("Failed to detect tech stack for {$url}: " . $e->getMessage());
            return [];
        }
    }
}