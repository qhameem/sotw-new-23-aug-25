<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminThemeFaviconBundleTest extends TestCase
{
    use RefreshDatabase;

    private string $settingsPath;
    private string $publicFaviconPath;
    private bool $settingsFileExisted = false;
    private ?string $originalSettingsContent = null;
    private bool $publicFaviconExisted = false;
    private ?string $originalPublicFaviconContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsPath = storage_path('app/theme_settings.json');
        $this->publicFaviconPath = public_path('favicon.ico');

        $this->settingsFileExisted = File::exists($this->settingsPath);
        $this->originalSettingsContent = $this->settingsFileExisted
            ? File::get($this->settingsPath)
            : null;

        $this->publicFaviconExisted = File::exists($this->publicFaviconPath);
        $this->originalPublicFaviconContent = $this->publicFaviconExisted
            ? File::get($this->publicFaviconPath)
            : null;
    }

    protected function tearDown(): void
    {
        if ($this->settingsFileExisted) {
            File::put($this->settingsPath, $this->originalSettingsContent ?? '');
        } else {
            File::delete($this->settingsPath);
        }

        if ($this->publicFaviconExisted) {
            File::put($this->publicFaviconPath, $this->originalPublicFaviconContent ?? '');
        } else {
            File::delete($this->publicFaviconPath);
        }

        parent::tearDown();
    }

    public function test_admin_can_upload_a_full_favicon_bundle(): void
    {
        Storage::fake('public');

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $response = $this
            ->from(route('admin.theme.edit'))
            ->actingAs($admin)
            ->put(route('admin.theme.update'), [
                'font_family' => 'Inter',
                'primary_color' => '#3b82f6',
                'favicon_bundle' => [
                    $this->fakeIcoUpload(),
                    UploadedFile::fake()->image('favicon-16x16.png', 16, 16),
                    UploadedFile::fake()->image('favicon-32x32.png', 32, 32),
                    UploadedFile::fake()->image('apple-touch-icon.png', 180, 180),
                    UploadedFile::fake()->image('android-chrome-192x192.png', 192, 192),
                    UploadedFile::fake()->image('android-chrome-512x512.png', 512, 512),
                    UploadedFile::fake()->createWithContent('site.webmanifest', json_encode([
                        'name' => 'Software on the Web',
                        'icons' => [],
                    ], JSON_THROW_ON_ERROR)),
                ],
            ]);

        $response->assertRedirect(route('admin.theme.edit'));
        $response->assertSessionHas('success');

        $settings = json_decode(File::get($this->settingsPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('favicon_url', $settings);
        $this->assertArrayHasKey('favicon_manifest_url', $settings);
        $this->assertMatchesRegularExpression(
            '#^theme/branding/favicon-set-[^/]+/favicon\.ico$#',
            $settings['favicon_url']
        );
        $this->assertSame(
            dirname($settings['favicon_url']) . '/site.webmanifest',
            $settings['favicon_manifest_url']
        );

        Storage::disk('public')->assertExists($settings['favicon_url']);
        Storage::disk('public')->assertExists(dirname($settings['favicon_url']) . '/favicon-16x16.png');
        Storage::disk('public')->assertExists(dirname($settings['favicon_url']) . '/favicon-32x32.png');
        Storage::disk('public')->assertExists(dirname($settings['favicon_url']) . '/apple-touch-icon.png');
        Storage::disk('public')->assertExists(dirname($settings['favicon_url']) . '/android-chrome-192x192.png');
        Storage::disk('public')->assertExists(dirname($settings['favicon_url']) . '/android-chrome-512x512.png');
        Storage::disk('public')->assertExists($settings['favicon_manifest_url']);

        $this->assertFileExists($this->publicFaviconPath);
        $this->assertGreaterThan(0, filesize($this->publicFaviconPath));
        $this->assertSame(
            md5_file(Storage::disk('public')->path($settings['favicon_url'])),
            md5_file($this->publicFaviconPath)
        );
    }

    public function test_favicon_bundle_rejects_unexpected_filenames(): void
    {
        Storage::fake('public');

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $response = $this
            ->from(route('admin.theme.edit'))
            ->actingAs($admin)
            ->put(route('admin.theme.update'), [
                'font_family' => 'Inter',
                'primary_color' => '#3b82f6',
                'favicon_bundle' => [
                    $this->fakeIcoUpload(),
                    UploadedFile::fake()->image('favicon-16x16.png', 16, 16),
                    UploadedFile::fake()->image('favicon-32x32.png', 32, 32),
                    UploadedFile::fake()->image('apple-touch-icon.png', 180, 180),
                    UploadedFile::fake()->image('favicon-64x64.png', 64, 64),
                ],
            ]);

        $response->assertRedirect(route('admin.theme.edit'));
        $response->assertSessionHasErrors('favicon_bundle');
    }

    private function fakeIcoUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'favicon.ico',
            hex2bin('0000010001001010000001002000680400001600000089504e470d0a1a0a')
        );
    }
}
