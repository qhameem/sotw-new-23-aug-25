<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBadgeEmbedCodeTest extends TestCase
{
    use RefreshDatabase;

    private ?string $originalBadgePngContents = null;
    private ?string $originalBadgeWebpContents = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $badgePngPath = public_path('images/badge.png');
        $badgeWebpPath = public_path('images/badge.webp');

        $this->originalBadgePngContents = is_file($badgePngPath) ? file_get_contents($badgePngPath) : null;
        $this->originalBadgeWebpContents = is_file($badgeWebpPath) ? file_get_contents($badgeWebpPath) : null;
    }

    protected function tearDown(): void
    {
        $this->restorePublicBadgeAsset('badge.png', $this->originalBadgePngContents);
        $this->restorePublicBadgeAsset('badge.webp', $this->originalBadgeWebpContents);

        parent::tearDown();
    }

    public function test_admin_can_save_custom_badge_share_code(): void
    {
        $admin = $this->createAdmin();
        $badgeEmbedCode = '  <a href="https://example.com" rel="dofollow">Badge</a>  ';

        $response = $this->actingAs($admin)->post(route('admin.settings.storeBadgeEmbedCode'), [
            'badge_embed_code' => $badgeEmbedCode,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Badge share code saved successfully.');

        $savedSettings = json_decode(Storage::disk('local')->get('settings.json'), true);

        $this->assertSame(
            '<a href="https://example.com" rel="dofollow">Badge</a>',
            $savedSettings['badge_embed_code'] ?? null
        );
    }

    public function test_badge_snippet_preview_uses_saved_badge_share_code(): void
    {
        $customBadgeEmbedCode = '<a href="https://custom.example.com" rel="dofollow"><img src="https://cdn.example.com/badge.png" alt="Custom badge" width="220"></a>';

        Storage::disk('local')->put('settings.json', json_encode([
            'badge_embed_code' => $customBadgeEmbedCode,
        ], JSON_PRETTY_PRINT));

        $this->getJson('/api/badge-snippet-preview')
            ->assertOk()
            ->assertJsonPath('snippet', $customBadgeEmbedCode);
    }

    public function test_badge_snippet_preview_falls_back_to_generated_code_when_saved_code_is_blank(): void
    {
        Storage::disk('local')->put('settings.json', json_encode([
            'badge_embed_code' => '   ',
        ], JSON_PRETTY_PRINT));

        $response = $this->getJson('/api/badge-snippet-preview')
            ->assertOk();

        $snippet = $response->json('snippet');

        $this->assertIsString($snippet);
        $this->assertStringContainsString('rel="dofollow"', $snippet);
        $this->assertStringContainsString('Featured on Software on the Web', $snippet);
        $this->assertStringContainsString('<img src="', $snippet);
    }

    public function test_badge_snippet_preview_uses_svg_with_png_fallback_when_both_assets_exist(): void
    {
        Storage::disk('local')->put('settings.json', json_encode([
            'badge_image_svg_url' => 'https://example.test/images/badge.svg',
            'badge_image_png_url' => 'https://example.test/images/badge.png',
            'badge_embed_code' => '',
        ], JSON_PRETTY_PRINT));

        $response = $this->getJson('/api/badge-snippet-preview')
            ->assertOk();

        $this->assertStringStartsWith('https://example.test/images/badge.svg', $response->json('badge_image_url'));
        $this->assertStringStartsWith('https://example.test/images/badge.svg', $response->json('badge_image_svg_url'));
        $this->assertStringStartsWith('https://example.test/images/badge.png', $response->json('badge_image_png_url'));

        $snippet = $response->json('snippet');

        $this->assertStringContainsString('<picture>', $snippet);
        $this->assertStringContainsString('<source srcset="https://example.test/images/badge.svg', $snippet);
        $this->assertStringContainsString('" type="image/svg+xml">', $snippet);
        $this->assertStringContainsString('<img src="https://example.test/images/badge.png', $snippet);
        $this->assertStringContainsString('alt="Featured on Software on the Web" width="200">', $snippet);
    }

    public function test_png_badge_upload_regenerates_legacy_webp_badge(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.settings.storeBadgeImage'), [
            'badge_image_png' => UploadedFile::fake()->image('badge.png', 300, 120),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Badge asset upload saved for: PNG and WEBP.');

        $savedSettings = json_decode(Storage::disk('local')->get('settings.json'), true);

        $this->assertSame(url('/images/badge.png'), $savedSettings['badge_image_png_url'] ?? null);
        $this->assertSame(url('/images/badge.webp'), $savedSettings['badge_image_webp_url'] ?? null);
        $this->assertFileExists(public_path('images/badge.png'));
        $this->assertFileExists(public_path('images/badge.webp'));
    }

    private function createAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function restorePublicBadgeAsset(string $filename, ?string $contents): void
    {
        $path = public_path('images/' . $filename);

        if ($contents === null) {
            if (is_file($path)) {
                @unlink($path);
            }

            return;
        }

        file_put_contents($path, $contents);
    }
}
