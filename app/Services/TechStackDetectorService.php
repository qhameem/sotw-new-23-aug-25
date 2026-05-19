<?php

namespace App\Services;

use App\Models\TechStack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechStackDetectorService
{
    private const GENERIC_GROUP_NAMES = [
        'registrar',
        'mobile',
        'framework',
        'link',
        'ssl',
        'hosting',
        'javascript',
        'language',
    ];

    public function detect(string $url): array
    {
        $detected = [];
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

                    if (isset($results['groups'])) {
                        foreach ($results['groups'] as $group) {
                            if (isset($group['name'])) {
                                $detected[] = $group['name'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to detect tech stack for {$url} with BuiltWith: " . $e->getMessage());
            }
        }

        $normalized = $this->normalizeDetectedTech($detected);
        if (!empty($normalized)) {
            return $normalized;
        }

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

                $normalized = $this->normalizeDetectedTech($detected);
                if (!empty($normalized)) {
                    return $normalized;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to detect tech stack for {$url} with Wappalyzer: " . $e->getMessage());
        }

        return $this->detectFromHtml($url);
    }

    private function normalizeDetectedTech(array $detected): array
    {
        $aliases = [
            'next' => 'Next.js',
            'next.js' => 'Next.js',
            'react' => 'React',
            'react.js' => 'React',
            'vue' => 'Vue.js',
            'vue.js' => 'Vue.js',
            'wordpress' => 'WordPress',
            'webflow' => 'Webflow',
            'shopify' => 'Shopify',
            'laravel' => 'Laravel',
            'php' => 'PHP',
            'python' => 'Python',
            'django' => 'Django',
            'flask' => 'Flask',
            'fastify' => 'Fastify',
            'svelte' => 'Svelte',
            'astro' => 'Astro',
            'angular' => 'Angular',
            'flutter' => 'Flutter',
            'react native' => 'React Native',
            'ruby on rails' => 'Ruby on Rails',
            'rails' => 'Ruby on Rails',
            'ruby' => 'Ruby',
            'java' => 'Java',
            'go' => 'Go',
            'swift' => 'Swift',
            'kotlin' => 'Kotlin',
            'redis' => 'Redis',
            'cloudflare' => 'Cloudflare',
            'aws' => 'AWS',
            'node.js' => 'Node.js',
            'nodejs' => 'Node.js',
            'node' => 'Node.js',
        ];

        $supported = TechStack::pluck('name')
            ->mapWithKeys(fn(string $name) => [mb_strtolower($name) => $name])
            ->all();

        $normalized = [];
        foreach ($detected as $name) {
            $lower = mb_strtolower(trim((string) $name));
            if ($lower === '' || in_array($lower, self::GENERIC_GROUP_NAMES, true)) {
                continue;
            }

            $candidate = $aliases[$lower] ?? $name;
            $candidateLower = mb_strtolower($candidate);

            if (isset($supported[$candidateLower])) {
                $normalized[] = $supported[$candidateLower];
            }
        }

        return array_values(array_unique($normalized));
    }

    private function detectFromHtml(string $url): array
    {
        try {
            $response = Http::timeout(15)->get($url);
            if (!$response->successful()) {
                return [];
            }

            $html = $response->body();
            $headers = array_change_key_case($response->headers(), CASE_LOWER);
            $detected = [];

            $patterns = [
                'Next.js' => ['/_next\//i', '/next-size-adjust/i', '/__NEXT_DATA__/i'],
                'React' => ['/react/i'],
                'Vue.js' => ['/vue/i'],
                'Svelte' => ['/svelte/i'],
                'Astro' => ['/astro/i'],
                'Webflow' => ['/webflow/i'],
                'WordPress' => ['/wp-content|wp-includes|wordpress/i'],
                'Shopify' => ['/shopify/i'],
                'Laravel' => ['/laravel/i'],
                'Django' => ['/django/i'],
                'Flask' => ['/flask/i'],
                'Fastify' => ['/fastify/i'],
                'PHP' => ['/php/i'],
                'Python' => ['/python/i'],
                'Cloudflare' => ['/cloudflare/i'],
                'AWS' => ['/aws|amazonaws/i'],
            ];

            foreach ($patterns as $name => $regexes) {
                foreach ($regexes as $regex) {
                    if (preg_match($regex, $html) === 1) {
                        $detected[] = $name;
                        break;
                    }
                }
            }

            $serverHeader = implode(' ', $headers['server'] ?? []);
            $poweredByHeader = implode(' ', $headers['x-powered-by'] ?? []);
            $headerText = $serverHeader . ' ' . $poweredByHeader;

            if (stripos($headerText, 'cloudflare') !== false) {
                $detected[] = 'Cloudflare';
            }

            return array_values(array_unique($detected));
        } catch (\Throwable $e) {
            Log::warning("Failed HTML heuristic tech detection for {$url}: " . $e->getMessage());
            return [];
        }
    }
}
