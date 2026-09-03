<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class AiCrawlerLogParser
{
    public function parse(string $line): ?array
    {
        $matched = preg_match(
            '/^(?<ip>\S+)\s+\S+\s+\S+\s+\[(?<time>[^\]]+)]\s+"(?<method>\S+)\s+(?<target>\S+)(?:\s+[^\"]+)?"\s+(?<status>\d{3})\s+\S+(?:\s+"[^"]*"\s+"(?<agent>[^"]*)")?/',
            trim($line),
            $matches
        );

        if (! $matched || empty($matches['agent'])) {
            return null;
        }

        $bot = $this->identifyBot($matches['agent']);
        if (! $bot) {
            return null;
        }

        $visitedAt = Carbon::createFromFormat('d/M/Y:H:i:s O', $matches['time']);
        $path = parse_url($matches['target'], PHP_URL_PATH) ?: '/';

        return [
            'bot' => $bot,
            'path' => mb_substr($path, 0, 2048),
            'status_code' => (int) $matches['status'],
            'visited_at' => $visitedAt,
        ];
    }

    public function identifyBot(string $userAgent): ?string
    {
        foreach (config('ai_crawlers.bots', []) as $name => $tokens) {
            foreach ($tokens as $token) {
                if (stripos($userAgent, $token) !== false) {
                    return $name;
                }
            }
        }

        return null;
    }
}
