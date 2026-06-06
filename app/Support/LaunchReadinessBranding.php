<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;

class LaunchReadinessBranding
{
    private const SETTINGS_FILE = 'settings.json';

    private const STORAGE_DIRECTORY = 'tools/launch-readiness-branding';

    private const GENERATED_ICON_DIRECTORY = self::STORAGE_DIRECTORY.'/generated';

    private const GENERATED_ICON_SPECS = [
        'favicon_16' => ['size' => 16, 'file' => 'favicon-16x16.png'],
        'favicon_32' => ['size' => 32, 'file' => 'favicon-32x32.png'],
        'apple_touch_icon' => ['size' => 180, 'file' => 'apple-touch-icon.png'],
        'android_chrome_192' => ['size' => 192, 'file' => 'android-chrome-192x192.png'],
        'android_chrome_512' => ['size' => 512, 'file' => 'android-chrome-512x512.png'],
    ];

    public function defaults(): array
    {
        return [
            'site_name' => 'Is Ready For Launch',
            'logo_path' => null,
            'favicon_path' => null,
            'og_image_path' => null,
            'generated_icons' => [],
            'homepage_h1' => 'Is your site ready for launch?',
            'homepage_title_tag' => 'Free Launch Readiness Checker | Audit Your Site Before You Launch',
            'homepage_meta_description' => 'Use this free launch readiness checker to audit your site before launch. Check SEO, trust, technical issues, and AI visibility in minutes.',
            'font_url' => null,
            'font_family' => 'Inter',
            'font_size' => 16,
            'font_color' => '#161616',
            'background_color' => '#f5f5f4',
        ];
    }

    public function get(): array
    {
        $settings = $this->loadSettings();
        $branding = $settings['tools'][ToolSettings::LAUNCH_READINESS_KEY]['branding'] ?? [];

        return $this->ensureGeneratedIcons(
            array_merge($this->defaults(), is_array($branding) ? $branding : [])
        );
    }

    public function save(array $branding): void
    {
        $settings = $this->loadSettings();
        $settings['tools'][ToolSettings::LAUNCH_READINESS_KEY]['branding'] = array_merge(
            $this->defaults(),
            $branding
        );

        Storage::disk('local')->put(self::SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function storeUploadedAsset(UploadedFile $file, string $prefix): string
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $fileName = $prefix.'-'.Str::uuid().'.'.$extension;

        return $file->storeAs(self::STORAGE_DIRECTORY, $fileName, 'public');
    }

    public function deleteAsset(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function publicLogoUrl(): string
    {
        return $this->versionedPublicUrl($this->get()['logo_path'] ?? null)
            ?? asset('images/tools/launch-readiness-icon.svg');
    }

    public function publicFaviconUrl(): ?string
    {
        $branding = $this->get();

        return $this->versionedPublicUrl($branding['favicon_path'] ?? null)
            ?? $this->versionedPublicUrl($branding['generated_icons']['favicon_32'] ?? null);
    }

    public function publicOgImageUrl(): ?string
    {
        return $this->versionedPublicUrl($this->get()['og_image_path'] ?? null);
    }

    public function publicGeneratedIconUrls(): array
    {
        $branding = $this->get();
        $paths = is_array($branding['generated_icons'] ?? null) ? $branding['generated_icons'] : [];
        $urls = [];

        foreach (array_keys(self::GENERATED_ICON_SPECS) as $key) {
            $url = $this->versionedPublicUrl($paths[$key] ?? null);

            if ($url) {
                $urls[$key] = $url;
            }
        }

        return $urls;
    }

    public function publicManifestUrl(): ?string
    {
        return $this->versionedPublicUrl($this->get()['generated_icons']['manifest'] ?? null);
    }

    public function siteName(): string
    {
        return (string) ($this->get()['site_name'] ?? $this->defaults()['site_name']);
    }

    public function homepageH1(): string
    {
        return $this->normalizedTextSetting('homepage_h1', $this->defaults()['homepage_h1']);
    }

    public function homepageTitleTag(): string
    {
        return $this->normalizedTextSetting('homepage_title_tag', $this->defaults()['homepage_title_tag']);
    }

    public function homepageMetaDescription(): string
    {
        return $this->normalizedTextSetting('homepage_meta_description', $this->defaults()['homepage_meta_description']);
    }

    public function fontUrl(): ?string
    {
        $fontUrl = trim((string) ($this->get()['font_url'] ?? ''));

        return $fontUrl !== '' ? $fontUrl : null;
    }

    public function fontFamily(): string
    {
        $fontFamily = trim((string) ($this->get()['font_family'] ?? ''));

        return $fontFamily !== '' ? $fontFamily : 'Inter';
    }

    public function fontCssStack(): string
    {
        $fontFamily = $this->fontFamily();
        $segments = array_values(array_filter(array_map('trim', explode(',', $fontFamily))));

        if ($segments === []) {
            return "'Inter', sans-serif";
        }

        $genericFamilies = [
            'serif',
            'sans-serif',
            'monospace',
            'cursive',
            'fantasy',
            'system-ui',
            'ui-serif',
            'ui-sans-serif',
            'ui-monospace',
            'ui-rounded',
            'emoji',
            'math',
            'fangsong',
            '-apple-system',
            'BlinkMacSystemFont',
        ];

        $normalized = array_map(function (string $segment) use ($genericFamilies) {
            $unquoted = trim($segment, "\"'");

            if (in_array($unquoted, $genericFamilies, true)) {
                return $unquoted;
            }

            return "'".str_replace("'", "\\'", $unquoted)."'";
        }, $segments);

        $hasGeneric = collect($segments)->contains(fn (string $segment) => in_array(trim($segment, "\"'"), $genericFamilies, true));

        if (! $hasGeneric) {
            $normalized[] = 'sans-serif';
        }

        return implode(', ', $normalized);
    }

    public function fontSize(): int
    {
        $size = (int) ($this->get()['font_size'] ?? 16);

        return min(20, max(14, $size));
    }

    public function fontColor(): string
    {
        $color = trim((string) ($this->get()['font_color'] ?? ''));

        return preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color) ? $color : '#161616';
    }

    public function backgroundColor(): string
    {
        $color = trim((string) ($this->get()['background_color'] ?? ''));

        return preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color) ? $color : '#f5f5f4';
    }

    public function extractFontFamiliesFromUrl(?string $url): array
    {
        if (blank($url)) {
            return [];
        }

        $queryString = parse_url($url, PHP_URL_QUERY) ?? '';

        if ($queryString === '') {
            return [];
        }

        $params = explode('&', $queryString);
        $fontFamilies = [];

        foreach ($params as $param) {
            if (! str_starts_with($param, 'family=')) {
                continue;
            }

            $family = substr($param, 7);
            $fontFamilyName = urldecode(explode(':', $family)[0]);

            if (preg_match('/^[a-zA-Z0-9\s\-]+$/', $fontFamilyName)) {
                $fontFamilies[] = trim($fontFamilyName);
            }
        }

        return array_values(array_unique(array_filter($fontFamilies)));
    }

    private function loadSettings(): array
    {
        if (! Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get(self::SETTINGS_FILE), true) ?: [];
    }

    private function versionedPublicUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::url($path).'?v='.Storage::disk('public')->lastModified($path);
    }

    private function normalizedTextSetting(string $key, string $fallback): string
    {
        $value = trim((string) ($this->get()[$key] ?? ''));

        return $value !== '' ? $value : $fallback;
    }

    private function ensureGeneratedIcons(array $branding): array
    {
        $generatedIcons = is_array($branding['generated_icons'] ?? null) ? $branding['generated_icons'] : [];
        $logoPath = $branding['logo_path'] ?? null;

        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            if ($generatedIcons !== []) {
                $this->deleteGeneratedIcons($generatedIcons);
                $branding['generated_icons'] = [];
                $this->save($branding);
            }

            return $branding;
        }

        if ($this->generatedIconsAreCurrent($logoPath, $generatedIcons)) {
            return $branding;
        }

        $this->deleteGeneratedIcons($generatedIcons);

        $branding['generated_icons'] = $this->generateGeneratedIcons(
            $logoPath,
            (string) ($branding['site_name'] ?? $this->defaults()['site_name']),
            (string) ($branding['background_color'] ?? $this->defaults()['background_color'])
        );

        $this->save($branding);

        return $branding;
    }

    private function generatedIconsAreCurrent(string $logoPath, array $generatedIcons): bool
    {
        if (($generatedIcons['source_logo_path'] ?? null) !== $logoPath) {
            return false;
        }

        foreach (array_keys(self::GENERATED_ICON_SPECS) as $key) {
            if (empty($generatedIcons[$key]) || ! Storage::disk('public')->exists($generatedIcons[$key])) {
                return false;
            }
        }

        return ! empty($generatedIcons['manifest']) && Storage::disk('public')->exists($generatedIcons['manifest']);
    }

    private function generateGeneratedIcons(string $logoPath, string $siteName, string $backgroundColor): array
    {
        $disk = Storage::disk('public');

        try {
            $contents = $disk->get($logoPath);
            $generatedIcons = [
                'source_logo_path' => $logoPath,
            ];

            foreach (self::GENERATED_ICON_SPECS as $key => $spec) {
                $image = $this->imageManager()->read($contents);
                $image->contain($spec['size'], $spec['size'], background: 'transparent');

                $path = self::GENERATED_ICON_DIRECTORY.'/'.Str::uuid().'-'.$spec['file'];
                $disk->put($path, (string) $image->encode(new PngEncoder()));
                $generatedIcons[$key] = $path;
            }

            $manifestPath = self::GENERATED_ICON_DIRECTORY.'/'.Str::uuid().'-site.webmanifest';
            $manifest = [
                'name' => $siteName,
                'short_name' => Str::limit($siteName, 12, ''),
                'icons' => [
                    [
                        'src' => Storage::url($generatedIcons['android_chrome_192']),
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ],
                    [
                        'src' => Storage::url($generatedIcons['android_chrome_512']),
                        'sizes' => '512x512',
                        'type' => 'image/png',
                    ],
                ],
                'theme_color' => $backgroundColor,
                'background_color' => $backgroundColor,
                'display' => 'standalone',
            ];

            $disk->put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $generatedIcons['manifest'] = $manifestPath;

            return $generatedIcons;
        } catch (\Throwable) {
            return [];
        }
    }

    private function deleteGeneratedIcons(array $generatedIcons): void
    {
        $paths = [];

        foreach (array_keys(self::GENERATED_ICON_SPECS) as $key) {
            if (! empty($generatedIcons[$key])) {
                $paths[] = $generatedIcons[$key];
            }
        }

        if (! empty($generatedIcons['manifest'])) {
            $paths[] = $generatedIcons['manifest'];
        }

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function imageManager(): ImageManager
    {
        return new ImageManager(
            Driver::class,
            autoOrientation: true,
            decodeAnimation: false,
            strip: true
        );
    }
}
