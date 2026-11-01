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

    public function test_admin_can_upload_a_single_png_and_generate_favicon_sizes(): void
    {
        Storage::fake('public');

        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->put(route('admin.theme.update'), [
            'font_family' => 'Inter',
            'primary_color' => '#ee3923',
            'site_favicon' => UploadedFile::fake()->image('favicon #ee3923.png', 256, 256),
        ]);

        $response->assertRedirect(route('admin.settings.index').'#settings-appearance');
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $settings = json_decode(File::get($this->settingsPath), true, 512, JSON_THROW_ON_ERROR);
        Storage::disk('public')->assertExists($settings['favicon_url']);

        $icoPath = dirname($settings['favicon_url']).'/favicon.ico';
        Storage::disk('public')->assertExists($icoPath);
        $ico = Storage::disk('public')->get($icoPath);
        $this->assertSame($ico, File::get($this->publicFaviconPath));
        $this->assertSame(['reserved' => 0, 'type' => 1, 'count' => 3], unpack('vreserved/vtype/vcount', substr($ico, 0, 6)));
        foreach ([16, 32, 48] as $index => $size) {
            $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbits/Vlength/Voffset', substr($ico, 6 + $index * 16, 16));
            $image = imagecreatefromstring(substr($ico, $entry['offset'], $entry['length']));
            $this->assertSame($size, imagesx($image));
            $this->assertSame($size, imagesy($image));
            imagedestroy($image);
        }

        foreach ([16, 32, 180, 192, 512] as $size) {
            $filename = match ($size) {
                180 => 'apple-touch-icon.png',
                192, 512 => "android-chrome-{$size}x{$size}.png",
                default => "favicon-{$size}x{$size}.png",
            };
            $path = dirname($settings['favicon_url']).'/'.$filename;
            Storage::disk('public')->assertExists($path);
            $dimensions = getimagesize(Storage::disk('public')->path($path));
            $this->assertSame($size, $dimensions[0]);
            $this->assertSame($size, $dimensions[1]);
            $this->assertSame('image/png', $dimensions['mime']);
        }
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

        $response->assertRedirect(route('admin.settings.index').'#settings-appearance');
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

        $editResponse = $this->actingAs($admin)->get(route('admin.settings.index'));

        $editResponse->assertOk();
        $editResponse->assertSee('Active Favicon Files');
        $editResponse->assertSee('favicon.ico');
        $editResponse->assertSee('site.webmanifest');
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

        $response->assertRedirect(route('admin.settings.index').'#settings-appearance');
        $response->assertSessionHasErrors('favicon_bundle');
    }

    public function test_admin_can_configure_product_card_hover_color(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $response = $this->actingAs($admin)->put(route('admin.theme.update'), [
            'font_family' => 'Inter',
            'primary_color' => '#3b82f6',
            'product_hover_color' => '#fff4e6',
        ]);

        $response->assertSessionHas('success');

        $settings = json_decode(File::get($this->settingsPath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('#fff4e6', $settings['product_hover_color']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Product Card Hover Background')
            ->assertSee('#fff4e6');
    }

    public function test_admin_can_configure_dark_mode_colors(): void
    {
        $adminRole = Role::create(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $darkColors = [
            'dark_navbar_bg_color' => '#101827',
            'dark_body_bg_color' => '#07101f',
            'dark_surface_color' => '#121c2d',
            'dark_muted_surface_color' => '#20304a',
            'dark_border_color' => '#40516d',
            'dark_font_color' => '#f1f5f9',
            'dark_body_text_color' => '#c0ccdc',
            'dark_product_hover_color' => '#26344d',
        ];

        $response = $this->actingAs($admin)->put(route('admin.theme.update'), [
            'font_family' => 'Inter',
            'primary_color' => '#3b82f6',
            ...$darkColors,
        ]);

        $response->assertSessionHas('success');

        $settings = json_decode(File::get($this->settingsPath), true, 512, JSON_THROW_ON_ERROR);
        foreach ($darkColors as $key => $value) {
            $this->assertSame($value, $settings[$key]);
        }

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Dark Mode Colors')
            ->assertSee('#07101f');
    }

    public function test_favicon_urls_change_when_contents_change_with_the_same_timestamp(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $path = 'theme/branding/test/favicon-source.png';
        config(['theme.favicon_url' => $path]);

        $disk->put($path, 'first-icon');
        touch($disk->path($path), 1700000000);
        $first = view('partials.theme.favicon-links')->render();

        $disk->put($path, 'replacement-icon');
        touch($disk->path($path), 1700000000);
        $second = view('partials.theme.favicon-links')->render();

        $this->assertStringContainsString('?v='.hash('sha256', 'first-icon'), $first);
        $this->assertStringContainsString('?v='.hash('sha256', 'replacement-icon'), $second);
        $this->assertNotSame($first, $second);
    }

    private function fakeIcoUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'favicon.ico',
            hex2bin('0000010001001010000001002000680400001600000089504e470d0a1a0a')
        );
    }
}
