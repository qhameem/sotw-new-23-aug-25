<?php

namespace Tests\Unit;

use App\Services\AiCrawlerLogParser;
use Tests\TestCase;

class AiCrawlerLogParserTest extends TestCase
{
    public function test_it_parses_a_known_ai_crawler_from_a_combined_access_log(): void
    {
        $line = '198.51.100.4 - - [03/Sep/2026:10:30:45 +0600] "GET /software/ai-automation?source=test HTTP/1.1" 200 1234 "-" "Mozilla/5.0 AppleWebKit/537.36; compatible; GPTBot/1.2; +https://openai.com/gptbot"';

        $record = app(AiCrawlerLogParser::class)->parse($line);

        $this->assertSame('OpenAI GPTBot', $record['bot']);
        $this->assertSame('/software/ai-automation', $record['path']);
        $this->assertSame(200, $record['status_code']);
        $this->assertSame('2026-09-03 10:30:45', $record['visited_at']->format('Y-m-d H:i:s'));
    }

    public function test_it_ignores_normal_browser_traffic(): void
    {
        $line = '198.51.100.4 - - [03/Sep/2026:10:30:45 +0600] "GET / HTTP/1.1" 200 1234 "-" "Mozilla/5.0 Chrome/140.0"';

        $this->assertNull(app(AiCrawlerLogParser::class)->parse($line));
    }
}
