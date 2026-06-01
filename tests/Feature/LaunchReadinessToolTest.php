<?php

namespace Tests\Feature;

use App\Models\ToolScan;
use App\Support\ToolSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LaunchReadinessToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_readiness_homepage_renders_pending_checks(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);

        $response = $this->get(route('launch-readiness.index', ['toolSlug' => $slug]));

        $response->assertOk();
        $response->assertSee('Is your site ready for launch?');
        $response->assertSee('Awaiting scan');
        $response->assertSee('Meta Information');
    }

    public function test_launch_readiness_scan_is_saved_and_redirects_to_results(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);

        Http::fake([
            'https://example.com' => Http::response('<html lang="en"><head><title>Example Launch Page</title><meta name="description" content="A thoughtful description for launch readiness testing."><link rel="canonical" href="https://example.com"><link rel="icon" href="/favicon.ico"><meta name="viewport" content="width=device-width, initial-scale=1"><meta property="og:title" content="Example"><meta property="og:description" content="OG description"><meta property="og:type" content="website"><meta property="og:image" content="https://example.com/og.png"><meta name="twitter:card" content="summary_large_image"><script type="application/ld+json">{}</script></head><body><header></header><main><h1>Example</h1><h2>Why we built this</h2><p>We built this product after working closely with customers and documenting our results in public.</p><a href="/pricing">Get started</a><a href="/privacy">Privacy</a><img src="/hero.png" alt="Hero"></main><footer></footer></body></html>', 200, [
                'Content-Encoding' => 'br',
                'Strict-Transport-Security' => 'max-age=31536000',
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]),
            'https://example.com/robots.txt' => Http::response("User-agent: *\nAllow: /", 200),
            'https://example.com/sitemap.xml' => Http::response('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>', 200),
        ]);

        $response = $this->post(route('launch-readiness.analyze', ['toolSlug' => $slug]), [
            'url' => 'example.com',
            'save_to_history' => '1',
        ]);

        $scan = ToolScan::query()->first();

        $response->assertRedirect(route('launch-readiness.results.show', ['toolSlug' => $slug, 'toolScan' => $scan]));
        $this->assertNotNull($scan);
        $this->assertTrue($scan->save_to_history);
        $this->assertSame('example.com', $scan->submitted_url);
        $this->assertSame('https://example.com', $scan->normalized_url);
        $this->assertGreaterThan(0, $scan->launch_score);
    }

    public function test_launch_readiness_history_page_renders_custom_layout(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);

        ToolScan::query()->create([
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => 'history-scan-token',
            'submitted_url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'final_url' => 'https://example.com',
            'final_host' => 'example.com',
            'launch_score' => 84,
            'seo_score' => 89,
            'ai_score' => 85,
            'trust_score' => 80,
            'passed_checks' => 31,
            'warning_checks' => 7,
            'failed_checks' => 1,
            'status_label' => 'Almost ready',
            'save_to_history' => true,
            'audit_payload' => [],
            'scanned_at' => now(),
        ]);

        $response = $this->get(route('launch-readiness.history', ['toolSlug' => $slug]));

        $response->assertOk();
        $response->assertSee('Search domains...');
        $response->assertSee('10 / page');
        $response->assertSee('Go to');
        $response->assertSee('Passed');
    }

    public function test_launch_readiness_history_page_only_shows_latest_saved_scan_per_url(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);

        ToolScan::query()->create([
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => 'older-history-scan-token',
            'submitted_url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'final_url' => 'https://example.com',
            'final_host' => 'example.com',
            'launch_score' => 61,
            'seo_score' => 70,
            'ai_score' => 64,
            'trust_score' => 60,
            'passed_checks' => 15,
            'warning_checks' => 8,
            'failed_checks' => 4,
            'status_label' => 'Needs improvement',
            'save_to_history' => true,
            'audit_payload' => [],
            'scanned_at' => now()->subHour(),
        ]);

        ToolScan::query()->create([
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => 'latest-history-scan-token',
            'submitted_url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'final_url' => 'https://example.com',
            'final_host' => 'example.com',
            'launch_score' => 92,
            'seo_score' => 95,
            'ai_score' => 90,
            'trust_score' => 88,
            'passed_checks' => 32,
            'warning_checks' => 2,
            'failed_checks' => 0,
            'status_label' => 'Ready',
            'save_to_history' => true,
            'audit_payload' => [],
            'scanned_at' => now(),
        ]);

        $response = $this->get(route('launch-readiness.history', ['toolSlug' => $slug]));

        $response->assertOk();
        $history = $response->viewData('history');

        $this->assertSame(1, $history->count());
        $this->assertSame(1, $history->total());
        $this->assertSame('latest-history-scan-token', $history->items()[0]->result_token);
        $response->assertDontSee('61');
        $response->assertSee('92');
    }
}
