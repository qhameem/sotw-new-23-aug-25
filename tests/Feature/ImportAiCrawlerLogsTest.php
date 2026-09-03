<?php

namespace Tests\Feature;

use App\Models\AiCrawlerDailyStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ImportAiCrawlerLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_incrementally_imports_ai_crawler_requests(): void
    {
        $path = storage_path('framework/testing-ai-crawlers.log');
        File::put($path, implode("\n", [
            '198.51.100.4 - - [03/Sep/2026:10:30:45 +0600] "GET /one HTTP/1.1" 200 1234 "-" "GPTBot/1.2"',
            '198.51.100.5 - - [03/Sep/2026:10:31:45 +0600] "GET /two HTTP/1.1" 404 50 "-" "ClaudeBot/1.0"',
            '198.51.100.6 - - [03/Sep/2026:10:32:45 +0600] "GET /human HTTP/1.1" 200 100 "-" "Mozilla/5.0 Chrome/140.0"',
        ])."\n");

        try {
            $this->artisan('ai-crawlers:import', ['--path' => $path])->assertSuccessful();
            $this->assertSame(2, AiCrawlerDailyStat::query()->sum('requests'));

            File::append($path, '198.51.100.7 - - [03/Sep/2026:10:33:45 +0600] "GET /three HTTP/1.1" 200 100 "-" "PerplexityBot/1.0"'."\n");
            $this->artisan('ai-crawlers:import', ['--path' => $path])->assertSuccessful();

            $this->assertSame(3, AiCrawlerDailyStat::query()->sum('requests'));
            $this->assertDatabaseHas('ai_crawler_daily_stats', ['bot' => 'PerplexityBot', 'path' => '/three']);
        } finally {
            File::delete($path);
        }
    }

    public function test_it_can_rebuild_totals_after_a_parser_or_log_format_change(): void
    {
        $path = storage_path('framework/testing-ai-crawlers-rebuild.log');
        File::put($path, '\'- 57.141.0.43 - - [02/Sep/2026:08:47:28 +0200] "GET /compare/a-vs-b HTTP/2" 200 21646 "-" "meta-externalagent/1.1"'."\n");

        try {
            $this->artisan('ai-crawlers:import', ['--path' => $path])->assertSuccessful();
            $this->assertSame(1, AiCrawlerDailyStat::query()->sum('requests'));

            $this->artisan('ai-crawlers:import', ['--path' => $path, '--rebuild' => true])->assertSuccessful();

            $this->assertSame(1, AiCrawlerDailyStat::query()->sum('requests'));
        } finally {
            File::delete($path);
        }
    }
}
