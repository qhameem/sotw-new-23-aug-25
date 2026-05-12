<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBadgeEmbedCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
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

    private function createAdmin(): User
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
