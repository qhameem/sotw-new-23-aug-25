<?php

namespace App\Console\Commands;

use App\Models\AiCrawlerDailyStat;
use App\Models\AiCrawlerLogState;
use App\Services\AiCrawlerLogParser;
use App\Services\HeaderStatsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAiCrawlerLogs extends Command
{
    protected $signature = 'ai-crawlers:import {--path= : Import one access log instead of configured paths}';

    protected $description = 'Incrementally import AI crawler requests from OpenLiteSpeed combined access logs';

    public function handle(AiCrawlerLogParser $parser, HeaderStatsService $headerStats): int
    {
        $paths = $this->option('path')
            ? [(string) $this->option('path')]
            : config('ai_crawlers.log_paths', []);

        if (empty($paths)) {
            $this->error('No access-log paths are configured.');

            return self::FAILURE;
        }

        $imported = 0;
        foreach ($paths as $path) {
            $imported += $this->importPath($path, $parser);
        }

        if ($imported > 0) {
            $headerStats->forget();
        }

        $this->info("Imported {$imported} AI crawler requests.");

        return self::SUCCESS;
    }

    private function importPath(string $path, AiCrawlerLogParser $parser): int
    {
        if (! is_readable($path)) {
            $this->warn("Access log is not readable: {$path}");

            return 0;
        }

        clearstatcache(true, $path);
        $pathHash = hash('sha256', $path);
        $inode = (string) (fileinode($path) ?: '');
        $size = (int) filesize($path);
        $state = AiCrawlerLogState::query()->firstOrNew(['path_hash' => $pathHash]);
        $offset = (int) ($state->byte_offset ?? 0);

        if (($state->inode && $state->inode !== $inode) || $size < $offset) {
            $offset = 0;
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            $this->warn("Could not open access log: {$path}");

            return 0;
        }

        fseek($handle, $offset);
        $aggregates = [];
        $imported = 0;

        while (($line = fgets($handle)) !== false) {
            $record = $parser->parse($line);
            if (! $record) {
                continue;
            }

            $date = $record['visited_at']->toDateString();
            $pathHashForRequest = hash('sha256', $record['path']);
            $key = implode('|', [$date, $record['bot'], $pathHashForRequest, $record['status_code']]);

            $aggregates[$key] ??= [
                'visited_on' => $date,
                'bot' => $record['bot'],
                'path_hash' => $pathHashForRequest,
                'path' => $record['path'],
                'status_code' => $record['status_code'],
                'requests' => 0,
                'last_seen_at' => $record['visited_at'],
            ];
            $aggregates[$key]['requests']++;
            if ($record['visited_at']->gt($aggregates[$key]['last_seen_at'])) {
                $aggregates[$key]['last_seen_at'] = $record['visited_at'];
            }
            $imported++;
        }

        $newOffset = ftell($handle);
        fclose($handle);

        DB::transaction(function () use ($aggregates, $state, $pathHash, $path, $inode, $newOffset) {
            foreach ($aggregates as $aggregate) {
                $stat = AiCrawlerDailyStat::query()->firstOrNew([
                    'visited_on' => $aggregate['visited_on'],
                    'bot' => $aggregate['bot'],
                    'path_hash' => $aggregate['path_hash'],
                    'status_code' => $aggregate['status_code'],
                ]);
                $stat->fill([
                    'path' => $aggregate['path'],
                    'last_seen_at' => $aggregate['last_seen_at'],
                ]);
                $stat->requests = (int) $stat->requests + $aggregate['requests'];
                $stat->save();
            }

            $state->fill([
                'path_hash' => $pathHash,
                'path' => $path,
                'inode' => $inode,
                'byte_offset' => $newOffset,
                'last_imported_at' => now(),
            ])->save();
        });

        return $imported;
    }
}
