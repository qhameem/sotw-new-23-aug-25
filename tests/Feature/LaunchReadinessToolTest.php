<?php

namespace Tests\Feature;

use App\Models\ToolScan;
use App\Models\ToolUser;
use App\Support\LaunchReadinessBranding;
use App\Support\LaunchReadinessGuestSession;
use App\Support\ToolSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaunchReadinessToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_readiness_homepage_renders_pending_checks(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $homepageH1 = app(LaunchReadinessBranding::class)->homepageH1();

        $response = $this->get(route('launch-readiness.index', ['toolSlug' => $slug]));

        $response->assertOk();
        $response->assertSee($homepageH1);
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
            'https://example.com/favicon.ico' => Http::response('ico', 200, [
                'Content-Type' => 'image/x-icon',
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
        $faviconCheck = collect($scan->audit_payload['categories'] ?? [])
            ->flatMap(fn (array $category) => $category['checks'] ?? [])
            ->firstWhere('key', 'favicon');
        $openGraphImageCheck = collect($scan->audit_payload['categories'] ?? [])
            ->flatMap(fn (array $category) => $category['checks'] ?? [])
            ->firstWhere('key', 'open_graph_image');
        $this->assertSame('Favicon found and reachable: favicon.ico (HTTP 200).', $faviconCheck['summary'] ?? null);
        $this->assertSame('https://example.com/favicon.ico', $faviconCheck['meta']['preview_url'] ?? null);
        $this->assertSame('https://example.com/og.png', $openGraphImageCheck['meta']['preview_url'] ?? null);
    }

    public function test_launch_readiness_meta_description_check_warns_when_description_is_too_short(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);

        Http::fake([
            'https://example.com' => Http::response('<html lang="en"><head><title>Example Launch Page</title><meta name="description" content="Short meta description for testing warnings."><link rel="canonical" href="https://example.com"><link rel="icon" href="/favicon.ico"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body><main><h1>Example</h1><p>Test page.</p></main></body></html>', 200),
            'https://example.com/favicon.ico' => Http::response('ico', 200, ['Content-Type' => 'image/x-icon']),
            'https://example.com/robots.txt' => Http::response("User-agent: *\nAllow: /", 200),
            'https://example.com/sitemap.xml' => Http::response('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>', 200),
        ]);

        $this->post(route('launch-readiness.analyze', ['toolSlug' => $slug]), [
            'url' => 'example.com',
            'save_to_history' => '1',
        ]);

        $scan = ToolScan::query()->firstOrFail();

        $metaDescriptionCheck = collect($scan->audit_payload['categories'] ?? [])
            ->flatMap(fn (array $category) => $category['checks'] ?? [])
            ->firstWhere('key', 'meta_description');

        $this->assertSame('warning', $metaDescriptionCheck['status'] ?? null);
        $this->assertStringContainsString('outside the recommended 120-160 character range', $metaDescriptionCheck['summary'] ?? '');
    }

    public function test_launch_readiness_title_tag_check_warns_when_title_length_is_outside_recommended_range(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);

        Http::fake([
            'https://example.com' => Http::response('<html lang="en"><head><title>Short title</title><meta name="description" content="This meta description is intentionally long enough to stay inside the recommended range for the launch readiness audit length check."><link rel="canonical" href="https://example.com"><link rel="icon" href="/favicon.ico"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body><main><h1>Example</h1><p>Test page.</p></main></body></html>', 200),
            'https://example.com/favicon.ico' => Http::response('ico', 200, ['Content-Type' => 'image/x-icon']),
            'https://example.com/robots.txt' => Http::response("User-agent: *\nAllow: /", 200),
            'https://example.com/sitemap.xml' => Http::response('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>', 200),
        ]);

        $this->post(route('launch-readiness.analyze', ['toolSlug' => $slug]), [
            'url' => 'example.com',
            'save_to_history' => '1',
        ]);

        $scan = ToolScan::query()->firstOrFail();

        $titleCheck = collect($scan->audit_payload['categories'] ?? [])
            ->flatMap(fn (array $category) => $category['checks'] ?? [])
            ->firstWhere('key', 'title_tag');

        $this->assertSame('warning', $titleCheck['status'] ?? null);
        $this->assertStringContainsString('outside the recommended 30-60 character range', $titleCheck['summary'] ?? '');
    }

    public function test_launch_readiness_meta_description_check_warns_when_description_is_too_long(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $longMetaDescription = str_repeat('This launch readiness test description intentionally runs long so the audit can verify recommended snippet-length warnings. ', 2);

        Http::fake([
            'https://example.com' => Http::response('<html lang="en"><head><title>Example Launch Page</title><meta name="description" content="' . e($longMetaDescription) . '"><link rel="canonical" href="https://example.com"><link rel="icon" href="/favicon.ico"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body><main><h1>Example</h1><p>Test page.</p></main></body></html>', 200),
            'https://example.com/favicon.ico' => Http::response('ico', 200, ['Content-Type' => 'image/x-icon']),
            'https://example.com/robots.txt' => Http::response("User-agent: *\nAllow: /", 200),
            'https://example.com/sitemap.xml' => Http::response('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>', 200),
        ]);

        $this->post(route('launch-readiness.analyze', ['toolSlug' => $slug]), [
            'url' => 'example.com',
            'save_to_history' => '1',
        ]);

        $scan = ToolScan::query()->firstOrFail();

        $metaDescriptionCheck = collect($scan->audit_payload['categories'] ?? [])
            ->flatMap(fn (array $category) => $category['checks'] ?? [])
            ->firstWhere('key', 'meta_description');

        $this->assertSame('warning', $metaDescriptionCheck['status'] ?? null);
        $this->assertStringContainsString('outside the recommended 120-160 character range', $metaDescriptionCheck['summary'] ?? '');
    }

    public function test_launch_readiness_open_graph_check_warns_when_og_title_or_description_length_is_outside_recommended_range(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $longOgDescription = str_repeat('This Open Graph description is intentionally verbose so the audit can verify recommended snippet-length warnings for social preview metadata. ', 2);

        Http::fake([
            'https://example.com' => Http::response('<html lang="en"><head><title>Example Launch Page With A Reasonable Length</title><meta name="description" content="This meta description is intentionally long enough to stay inside the recommended range for the launch readiness audit length check."><meta property="og:title" content="Tiny OG"><meta property="og:description" content="' . e($longOgDescription) . '"><meta property="og:type" content="website"><link rel="canonical" href="https://example.com"><link rel="icon" href="/favicon.ico"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body><main><h1>Example</h1><p>Test page.</p></main></body></html>', 200),
            'https://example.com/favicon.ico' => Http::response('ico', 200, ['Content-Type' => 'image/x-icon']),
            'https://example.com/robots.txt' => Http::response("User-agent: *\nAllow: /", 200),
            'https://example.com/sitemap.xml' => Http::response('<?xml version="1.0" encoding="UTF-8"?><urlset></urlset>', 200),
        ]);

        $this->post(route('launch-readiness.analyze', ['toolSlug' => $slug]), [
            'url' => 'example.com',
            'save_to_history' => '1',
        ]);

        $scan = ToolScan::query()->firstOrFail();

        $openGraphCheck = collect($scan->audit_payload['categories'] ?? [])
            ->flatMap(fn (array $category) => $category['checks'] ?? [])
            ->firstWhere('key', 'open_graph_basics');

        $this->assertSame('warning', $openGraphCheck['status'] ?? null);
        $this->assertStringContainsString('recommended lengths could be improved', $openGraphCheck['summary'] ?? '');
        $this->assertStringContainsString('og:title', $openGraphCheck['summary'] ?? '');
        $this->assertStringContainsString('og:description', $openGraphCheck['summary'] ?? '');
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
            'status_label' => 'Good score',
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
            'status_label' => 'Fair score',
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
            'status_label' => 'Excellent score',
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
        $response->assertSee('92');
    }

    public function test_authenticated_tool_user_can_view_dashboard(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $user = ToolUser::query()->create([
            'name' => 'Quazi Hameem Mahmud',
            'email' => 'qhameemb@gmail.com',
        ]);

        ToolScan::query()->create([
            'tool_user_id' => $user->id,
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => 'dashboard-history-scan-token',
            'submitted_url' => 'https://example.com',
            'normalized_url' => 'https://example.com',
            'final_url' => 'https://example.com',
            'final_host' => 'example.com',
            'launch_score' => 85,
            'seo_score' => 88,
            'ai_score' => 81,
            'trust_score' => 80,
            'passed_checks' => 28,
            'warning_checks' => 10,
            'failed_checks' => 1,
            'status_label' => 'Good score',
            'save_to_history' => true,
            'audit_payload' => [],
            'scanned_at' => now(),
        ]);

        $response = $this->actingAs($user, 'tool_user')
            ->get(route('launch-readiness.dashboard', ['toolSlug' => $slug]));

        $response->assertOk();
        $response->assertSee('Recent Tests');
        $response->assertSee('Remaining today');
        $response->assertSee('https://example.com');
        $response->assertSee('qhameemb@gmail.com');
    }

    public function test_claimed_guest_scans_are_shown_on_dashboard(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $userAgent = 'LaunchReadinessTestAgent';
        $sessionId = 'launch-readiness-guest-session';
        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => $userAgent,
        ]);
        $session = app('session')->driver();
        $session->setId($sessionId);
        $session->start();
        $request->setLaravelSession($session);

        $guestHash = app(LaunchReadinessGuestSession::class)->hash($request);

        ToolScan::query()->create([
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => 'guest-dashboard-scan-token',
            'submitted_url' => 'https://guest-example.com',
            'normalized_url' => 'https://guest-example.com',
            'final_url' => 'https://guest-example.com',
            'final_host' => 'guest-example.com',
            'guest_hash' => $guestHash,
            'launch_score' => 79,
            'seo_score' => 80,
            'ai_score' => 74,
            'trust_score' => 78,
            'passed_checks' => 24,
            'warning_checks' => 8,
            'failed_checks' => 2,
            'status_label' => 'Good score',
            'save_to_history' => false,
            'audit_payload' => [],
            'scanned_at' => now(),
        ]);

        $user = ToolUser::query()->create([
            'name' => 'Signed In User',
            'email' => 'signin@example.com',
        ]);

        app(LaunchReadinessGuestSession::class)->claimScansForUser($user, $request);

        $scan = ToolScan::query()->where('result_token', 'guest-dashboard-scan-token')->firstOrFail();

        $this->assertSame($user->id, $scan->tool_user_id);
        $this->assertNull($scan->guest_hash);

        $dashboardResponse = $this->actingAs($user, 'tool_user')
            ->get(route('launch-readiness.dashboard', ['toolSlug' => $slug]));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('https://guest-example.com');
    }

    public function test_tool_user_can_update_profile_settings(): void
    {
        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $user = ToolUser::query()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user, 'tool_user')
            ->patch(route('launch-readiness.settings.profile.update', ['toolSlug' => $slug]), [
                'name' => 'New Name',
                'email' => 'qhameemb@gmail.com',
            ])
            ->assertRedirect(route('launch-readiness.settings', ['toolSlug' => $slug, 'tab' => 'profile']));

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('qhameemb@gmail.com', $user->email);
        $this->assertTrue($user->isAdmin());
    }

    public function test_admin_tool_user_can_update_dashboard_branding(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $user = ToolUser::query()->create([
            'name' => 'Admin User',
            'email' => 'qhameemb@gmail.com',
        ]);

        $response = $this->actingAs($user, 'tool_user')
            ->patch(route('launch-readiness.dashboard.branding.update', ['toolSlug' => $slug]), [
                'site_name' => 'Website Launch Checker',
                'tool_slug' => 'website-launch-checker',
                'homepage_h1' => 'Check if your website is ready to launch',
                'homepage_title_tag' => 'Free Website Launch Checker | Audit Your Site Before You Launch',
                'homepage_meta_description' => 'Use this free Website Launch Checker to audit your site before launch. Check SEO, trust, technical issues, and AI visibility in minutes.',
                'font_url' => 'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&display=swap',
                'font_size' => 15,
                'font_color' => '#1f2937',
                'background_color' => '#f3f4f6',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
                'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
                'og_image' => UploadedFile::fake()->image('og-image.png', 1200, 630),
            ]);

        $response->assertRedirect(route('launch-readiness.settings', ['toolSlug' => 'website-launch-checker', 'tab' => 'branding']));

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        $branding = $settings['tools'][ToolSettings::LAUNCH_READINESS_KEY]['branding'] ?? [];

        $this->assertSame('Website Launch Checker', $branding['site_name'] ?? null);
        $this->assertSame('website-launch-checker', $settings['tools'][ToolSettings::LAUNCH_READINESS_KEY]['slug'] ?? null);
        $this->assertSame('Check if your website is ready to launch', $branding['homepage_h1'] ?? null);
        $this->assertSame('Free Website Launch Checker | Audit Your Site Before You Launch', $branding['homepage_title_tag'] ?? null);
        $this->assertSame('Use this free Website Launch Checker to audit your site before launch. Check SEO, trust, technical issues, and AI visibility in minutes.', $branding['homepage_meta_description'] ?? null);
        $this->assertSame('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700&display=swap', $branding['font_url'] ?? null);
        $this->assertSame('Manrope', $branding['font_family'] ?? null);
        $this->assertSame(15, $branding['font_size'] ?? null);
        $this->assertSame('#1f2937', $branding['font_color'] ?? null);
        $this->assertSame('#f3f4f6', $branding['background_color'] ?? null);
        $this->assertNotEmpty($branding['logo_path'] ?? null);
        $this->assertNotEmpty($branding['favicon_path'] ?? null);
        $this->assertNotEmpty($branding['og_image_path'] ?? null);
        $this->assertSame($branding['logo_path'], $branding['generated_icons']['source_logo_path'] ?? null);
        $this->assertNotEmpty($branding['generated_icons']['favicon_16'] ?? null);
        $this->assertNotEmpty($branding['generated_icons']['favicon_32'] ?? null);
        $this->assertNotEmpty($branding['generated_icons']['apple_touch_icon'] ?? null);
        $this->assertNotEmpty($branding['generated_icons']['android_chrome_192'] ?? null);
        $this->assertNotEmpty($branding['generated_icons']['android_chrome_512'] ?? null);
        $this->assertNotEmpty($branding['generated_icons']['manifest'] ?? null);
        Storage::disk('public')->assertExists($branding['logo_path']);
        Storage::disk('public')->assertExists($branding['favicon_path']);
        Storage::disk('public')->assertExists($branding['og_image_path']);
        Storage::disk('public')->assertExists($branding['generated_icons']['favicon_16']);
        Storage::disk('public')->assertExists($branding['generated_icons']['favicon_32']);
        Storage::disk('public')->assertExists($branding['generated_icons']['apple_touch_icon']);
        Storage::disk('public')->assertExists($branding['generated_icons']['android_chrome_192']);
        Storage::disk('public')->assertExists($branding['generated_icons']['android_chrome_512']);
        Storage::disk('public')->assertExists($branding['generated_icons']['manifest']);

        $dashboardResponse = $this->actingAs($user, 'tool_user')
            ->get(route('launch-readiness.settings', ['toolSlug' => 'website-launch-checker', 'tab' => 'branding']));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Project Branding');
        $dashboardResponse->assertSee('Website Launch Checker');
        $dashboardResponse->assertSee('/tools/website-launch-checker');
        $dashboardResponse->assertSee('Check if your website is ready to launch');
        $dashboardResponse->assertSee('fonts.googleapis.com/css2?family=Manrope', false);

        $homepageResponse = $this->get(route('launch-readiness.index', ['toolSlug' => 'website-launch-checker']));
        $homepageResponse->assertOk();
        $homepageResponse->assertSee('Check if your website is ready to launch');
        $homepageResponse->assertSee('Free Website Launch Checker | Audit Your Site Before You Launch', false);
        $homepageResponse->assertSee('Use this free Website Launch Checker to audit your site before launch. Check SEO, trust, technical issues, and AI visibility in minutes.', false);
        $homepageResponse->assertSee(Storage::url($branding['og_image_path']), false);
    }

    public function test_non_admin_tool_user_cannot_update_dashboard_branding(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $slug = app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY);
        $user = ToolUser::query()->create([
            'name' => 'Regular User',
            'email' => 'member@example.com',
        ]);

        $this->actingAs($user, 'tool_user')
            ->patch(route('launch-readiness.dashboard.branding.update', ['toolSlug' => $slug]), [
                'site_name' => 'Should Not Save',
                'font_size' => 16,
                'font_color' => '#161616',
                'background_color' => '#f5f5f4',
            ])
            ->assertForbidden();
    }
}
