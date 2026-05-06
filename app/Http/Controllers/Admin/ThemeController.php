<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Log;

class ThemeController extends Controller
{
    private const FAVICON_BUNDLE_SPECS = [
        'favicon.ico' => [
            'extensions' => ['ico'],
            'mime_types' => ['image/vnd.microsoft.icon', 'image/x-icon', 'application/octet-stream', 'application/ico', 'image/ico'],
            'image' => false,
        ],
        'favicon-16x16.png' => [
            'extensions' => ['png'],
            'mime_types' => ['image/png'],
            'image' => true,
            'dimensions' => [16, 16],
        ],
        'favicon-32x32.png' => [
            'extensions' => ['png'],
            'mime_types' => ['image/png'],
            'image' => true,
            'dimensions' => [32, 32],
        ],
        'apple-touch-icon.png' => [
            'extensions' => ['png'],
            'mime_types' => ['image/png'],
            'image' => true,
            'dimensions' => [180, 180],
        ],
        'android-chrome-192x192.png' => [
            'extensions' => ['png'],
            'mime_types' => ['image/png'],
            'image' => true,
            'dimensions' => [192, 192],
        ],
        'android-chrome-512x512.png' => [
            'extensions' => ['png'],
            'mime_types' => ['image/png'],
            'image' => true,
            'dimensions' => [512, 512],
        ],
        'site.webmanifest' => [
            'extensions' => ['webmanifest'],
            'mime_types' => ['application/manifest+json', 'application/json', 'text/plain'],
            'image' => false,
        ],
    ];

    private const REQUIRED_FAVICON_BUNDLE_FILES = [
        'favicon.ico',
        'favicon-16x16.png',
        'favicon-32x32.png',
        'apple-touch-icon.png',
    ];

    private string $settingsPath;

    public function __construct()
    {
        $this->settingsPath = storage_path('app/theme_settings.json');
    }

    public function edit()
    {
        $settings = $this->loadSettings();

        $currentFontUrl = array_key_exists('font_url', $settings) ? $settings['font_url'] : Config::get('theme.font_url');
        $currentFontFamily = array_key_exists('font_family', $settings) ? $settings['font_family'] : Config::get('theme.font_family');
        $currentFontColor = $settings['font_color'] ?? Config::get('theme.font_color', '#111827');
        $currentBodyTextColor = $settings['body_text_color'] ?? Config::get('theme.body_text_color', '#4b5563');
        $availableFonts = $settings['font_families'] ?? [];
        $currentPrimaryColor = $settings['primary_color'] ?? Config::get('theme.primary_color');
        $currentLogoUrl = isset($settings['logo_url']) && $settings['logo_url'] ? Storage::url($settings['logo_url']) : null;
        $currentLogoAltText = $settings['logo_alt_text'] ?? '';
        $currentFaviconUrl = isset($settings['favicon_url']) && $settings['favicon_url'] ? Storage::url($settings['favicon_url']) : null;
        $currentPrimaryButtonTextColor = $settings['primary_button_text_color'] ?? null;
        $currentSubmissionBgUrl = isset($settings['submission_bg_url']) && $settings['submission_bg_url'] ? Storage::url($settings['submission_bg_url']) : null;
        $currentNavbarBgColor = $settings['navbar_bg_color'] ?? '#ffffff';
        $currentBodyBgColor   = $settings['body_bg_color']   ?? '#ffffff';

        // Define default/placeholder URLs if needed, e.g., for the view's x-data
        $defaultLogoUrl = ''; // e.g., asset('images/default-logo.png');
        $defaultFaviconUrl = ''; // e.g., asset('images/default-favicon.ico');
        $defaultSubmissionBgUrl = asset('images/submission-pattern.png');

        return view('admin.theme.edit', compact(
            'currentFontUrl',
            'currentFontFamily',
            'currentFontColor',
            'currentBodyTextColor',
            'availableFonts',
            'currentPrimaryColor',
            'currentLogoUrl',
            'currentLogoAltText',
            'currentFaviconUrl',
            'defaultLogoUrl',
            'defaultFaviconUrl',
            'currentPrimaryButtonTextColor',
            'currentSubmissionBgUrl',
            'defaultSubmissionBgUrl',
            'currentNavbarBgColor',
            'currentBodyBgColor'
        ));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'font_url' => [
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    if (blank($value)) {
                        return;
                    }

                    if (!Str::startsWith($value, 'https://fonts.googleapis.com/css')) {
                        $fail('The ' . $attribute . ' must be a valid Google Fonts API URL.');
                    }
                },
            ],
            'font_family' => ['required', 'string', 'max:500'],
            'primary_color' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Check if it's a valid hex color
                    if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $value)) {
                        return;
                    }
                    // Check if it's a valid Tailwind color class format (e.g., blue-500)
                    if (preg_match('/^[a-z]+-[0-9]{2,3}$/', $value)) {
                        return;
                    }
                    $fail('The ' . $attribute . ' must be a valid hex color or a Tailwind CSS color class (e.g., blue-500).');
                },
            ],
            'font_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'body_text_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            // Removed 'image' rule to better support SVG. Mimes rule will handle type.
            'site_logo' => 'nullable|file|mimes:svg,png,jpg,jpeg,gif|max:2048',
            'logo_alt_text' => 'nullable|string|max:255',
            'remove_logo' => 'sometimes|boolean',
            'site_favicon' => 'nullable|file|mimes:ico,png,svg|max:100', // Max 100KB
            'favicon_bundle' => 'nullable|array',
            'favicon_bundle.*' => 'nullable|file|max:1024',
            'remove_favicon' => 'sometimes|boolean',
            'primary_button_text_color' => [
                'nullable',
                'string',
                'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'
            ],
            'submission_bg' => 'nullable|file|mimes:svg,png,jpg,jpeg,webp|max:5120', // Max 5MB
            'remove_submission_bg' => 'sometimes|boolean',
            'navbar_bg_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'body_bg_color'   => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $faviconBundleFiles = array_values(array_filter($request->file('favicon_bundle', [])));

        if (!empty($faviconBundleFiles) && $request->hasFile('site_favicon')) {
            return redirect()->back()
                ->withErrors(['favicon_bundle' => 'Upload either a favicon bundle or a single favicon file, not both at the same time.'])
                ->withInput();
        }

        $faviconBundleErrors = $this->validateFaviconBundle($faviconBundleFiles);
        if ($faviconBundleErrors !== []) {
            return redirect()->back()
                ->withErrors($faviconBundleErrors)
                ->withInput();
        }

        $settings = $this->loadSettings();

        // Font settings
        $fontUrl = trim((string) $request->input('font_url', ''));
        $fontUrl = $fontUrl === '' ? null : $fontUrl;
        $customFontFamily = trim((string) $request->input('font_family', ''));
        $fontFamilies = $this->extractFontFamiliesFromUrl($fontUrl);

        if (empty($fontFamilies) && $fontUrl) { // only error if URL was provided but family extraction failed
            return redirect()->back()
                ->withErrors(['font_url' => 'Could not automatically extract font family name. Ensure a valid Google Font URL.'])
                ->withInput();
        }

        $settings['font_url'] = $fontUrl;
        $settings['font_families'] = $fontFamilies;

        $defaultFont = $request->input('default_font_family');
        if (!empty($fontFamilies)) {
            if ($defaultFont && in_array($defaultFont, $fontFamilies)) {
                $settings['font_family'] = $defaultFont;
            } elseif ($customFontFamily !== '') {
                $settings['font_family'] = $customFontFamily;
            } else {
                $settings['font_family'] = $fontFamilies[0];
            }
        } else {
            $settings['font_family'] = $customFontFamily;
        }
        $settings['primary_color'] = $request->input('primary_color');
        $settings['font_color'] = $request->input('font_color', '#111827');
        $settings['body_text_color'] = $request->input('body_text_color', '#4b5563');
        $settings['primary_button_text_color'] = $request->input('primary_button_text_color');
        $settings['navbar_bg_color'] = $request->input('navbar_bg_color', '#ffffff');
        $settings['body_bg_color']   = $request->input('body_bg_color',   '#ffffff');

        // Logo Management
        if ($request->input('remove_logo')) {
            $this->deleteStoredFile($settings['logo_url'] ?? null);
            $settings['logo_url'] = null;
            $settings['logo_alt_text'] = null;
        } elseif ($request->hasFile('site_logo')) {
            $this->deleteStoredFile($settings['logo_url'] ?? null);
            $settings['logo_url'] = $this->storeUploadedFile($request->file('site_logo'), 'logo', 'theme/branding');
        }
        // Always update alt text if provided, even if logo isn't changed (unless it's removed)
        if (!$request->input('remove_logo')) {
            $settings['logo_alt_text'] = $request->input('logo_alt_text', $settings['logo_alt_text'] ?? '');
        }


        // Favicon Management
        if ($request->input('remove_favicon')) {
            $this->deleteFaviconAssetSet($settings);
            $settings['favicon_url'] = null;
            $settings['favicon_manifest_url'] = null;
            $this->restorePublicRootFavicon();
        } elseif (!empty($faviconBundleFiles)) {
            $this->deleteFaviconAssetSet($settings);

            $faviconBundle = $this->storeFaviconBundle($faviconBundleFiles);
            $settings['favicon_url'] = $faviconBundle['favicon_url'];
            $settings['favicon_manifest_url'] = $faviconBundle['favicon_manifest_url'];

            $this->syncPublicRootFavicon($faviconBundle['favicon_url']);
        } elseif ($request->hasFile('site_favicon')) {
            $this->deleteFaviconAssetSet($settings);

            $uploadedFaviconFile = $request->file('site_favicon');
            $originalFaviconPath = $this->storeSingleFaviconSet($uploadedFaviconFile);
            $settings['favicon_url'] = $originalFaviconPath;
            $settings['favicon_manifest_url'] = null;

            // Generate different sizes if it's a PNG
            if (strtolower($uploadedFaviconFile->getClientOriginalExtension()) === 'png') {
                try {
                    $this->generateFaviconVersions($uploadedFaviconFile, $originalFaviconPath);
                } catch (\Exception $e) {
                    Log::error('Failed to generate favicon versions: ' . $e->getMessage());
                }
            }

            if (strtolower(pathinfo($originalFaviconPath, PATHINFO_EXTENSION)) === 'ico') {
                $this->syncPublicRootFavicon($originalFaviconPath);
            }
        }

        // Submission Page Background Management
        if ($request->input('remove_submission_bg')) {
            $this->deleteStoredFile($settings['submission_bg_url'] ?? null);
            $settings['submission_bg_url'] = null;
        } elseif ($request->hasFile('submission_bg')) {
            $this->deleteStoredFile($settings['submission_bg_url'] ?? null);
            $settings['submission_bg_url'] = $this->storeUploadedFile($request->file('submission_bg'), 'submission_bg', 'theme/backgrounds');
        }

        try {
            File::put($this->settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

            // Update the tailwind-theme.json file
            $this->updateTailwindThemeConfig($settings['font_families'] ?? []);

            return redirect()->back()->with('success', 'Theme settings updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update theme settings: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Failed to save theme settings. Please check storage permissions and logs.'])
                ->withInput();
        }
    }

    private function loadSettings(): array
    {
        if (!File::exists($this->settingsPath)) {
            return [
                'font_url' => Config::get('theme.font_url'), // Default from config
                'font_family' => Config::get('theme.font_family'),
                'font_families' => [],
                'font_color' => Config::get('theme.font_color', '#111827'),
                'body_text_color' => Config::get('theme.body_text_color', '#4b5563'),
                'primary_color' => Config::get('theme.primary_color', '#3b82f6'),
                'primary_button_text_color' => Config::get('theme.primary_button_text_color'),
                'logo_url' => null,
                'logo_alt_text' => null,
                'favicon_url' => null,
                'favicon_manifest_url' => null,
                'submission_bg_url' => null,
                'navbar_bg_color' => Config::get('theme.navbar_bg_color', '#ffffff'),
                'body_bg_color'   => Config::get('theme.body_bg_color',   '#ffffff'),
            ];
        }

        $settings = json_decode(File::get($this->settingsPath), true) ?: [];

        // Ensure font_families is always present and correct, deriving from font_url if missing.
        if (!isset($settings['font_families']) || empty($settings['font_families'])) {
            $settings['font_families'] = $this->extractFontFamiliesFromUrl($settings['font_url'] ?? null);
        }

        return $settings;
    }

    private function storeUploadedFile(UploadedFile $file, string $fileNamePrefix, string $directory = 'theme'): string
    {
        $fileName = $fileNamePrefix . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        // Store in storage/app/public/{directory}
        return $file->storeAs($directory, $fileName, 'public');
    }

    private function storeUploadedFileAs(UploadedFile $file, string $directory, string $fileName): string
    {
        return $file->storeAs($directory, $fileName, 'public');
    }

    private function deleteStoredFile(?string $filePath): void
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    private function extractFontFamiliesFromUrl(?string $url = null): array
    {
        if (empty($url)) {
            return [];
        }

        $queryString = parse_url($url, PHP_URL_QUERY) ?? '';
        if (empty($queryString)) {
            return [];
        }

        $params = explode('&', $queryString);
        $fontFamilies = [];

        foreach ($params as $param) {
            if (str_starts_with($param, 'family=')) {
                $family = substr($param, 7); // Remove 'family='
                $fontFamilyName = urldecode(explode(':', $family)[0]); // Decode URL-encoded characters like '+'

                // Basic validation for font name
                if (preg_match('/^[a-zA-Z0-9\s\-]+$/', $fontFamilyName)) {
                    $fontFamilies[] = trim($fontFamilyName);
                }
            }
        }

        return array_unique($fontFamilies); // Return unique font families
    }

    private function generateFaviconVersions(UploadedFile $uploadedFile, string $originalStoredPath): void
    {
        $sourcePath = Storage::disk('public')->path($originalStoredPath);
        $directory = dirname($originalStoredPath); // e.g., 'theme/branding'

        $sizes = [
            'favicon-16x16.png' => 16,
            'favicon-32x32.png' => 32,
            'apple-touch-icon.png' => 180, // Standard for modern Apple devices
            'android-chrome-192x192.png' => 192,
            'android-chrome-512x512.png' => 512,
        ];

        foreach ($sizes as $filename => $size) {
            try {
                $image = Image::read($sourcePath);
                $image->resize($size, $size); // Maintain aspect ratio, fit within dimensions

                $destinationPath = Storage::disk('public')->path($directory . '/' . $filename);
                $image->save($destinationPath);
            } catch (\Exception $e) {
                Log::error("Failed to generate favicon version {$filename}: " . $e->getMessage());
                // Continue to next size if one fails
            }
        }
    }

    private function deleteGeneratedFaviconVersions(?string $originalFaviconPath): void
    {
        if (!$originalFaviconPath) {
            return;
        }

        $directory = dirname($originalFaviconPath);
        $generatedFiles = [
            $directory . '/favicon-16x16.png',
            $directory . '/favicon-32x32.png',
            $directory . '/apple-touch-icon.png',
            $directory . '/android-chrome-192x192.png',
            $directory . '/android-chrome-512x512.png',
            $directory . '/site.webmanifest',
        ];

        foreach ($generatedFiles as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
        }
    }

    private function validateFaviconBundle(array $files): array
    {
        if ($files === []) {
            return [];
        }

        $errors = [];
        $uploadedNames = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $originalName = strtolower($file->getClientOriginalName());
            $uploadedNames[] = $originalName;

            if (!isset(self::FAVICON_BUNDLE_SPECS[$originalName])) {
                $errors['favicon_bundle'][] = "Unexpected favicon bundle file: {$originalName}.";
                continue;
            }

            if (count(array_keys($uploadedNames, $originalName, true)) > 1) {
                $errors['favicon_bundle'][] = "Duplicate favicon bundle file detected: {$originalName}.";
                continue;
            }

            $spec = self::FAVICON_BUNDLE_SPECS[$originalName];
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $spec['extensions'], true)) {
                $errors['favicon_bundle'][] = "{$originalName} must use the ." . implode(', .', $spec['extensions']) . ' extension.';
                continue;
            }

            if ($originalName === 'site.webmanifest') {
                $manifest = json_decode((string) file_get_contents($file->getRealPath()), true);
                if (!is_array($manifest)) {
                    $errors['favicon_bundle'][] = 'site.webmanifest must contain valid JSON.';
                }
                continue;
            }

            $mimeType = strtolower((string) $file->getMimeType());
            if ($mimeType !== '' && !in_array($mimeType, $spec['mime_types'], true)) {
                $errors['favicon_bundle'][] = "{$originalName} has an invalid file type.";
                continue;
            }

            if (!empty($spec['image'])) {
                $dimensions = @getimagesize($file->getRealPath());
                if (!$dimensions || !isset($dimensions[0], $dimensions[1])) {
                    $errors['favicon_bundle'][] = "{$originalName} must be a readable image file.";
                    continue;
                }

                [$expectedWidth, $expectedHeight] = $spec['dimensions'];
                if ($dimensions[0] !== $expectedWidth || $dimensions[1] !== $expectedHeight) {
                    $errors['favicon_bundle'][] = "{$originalName} must be {$expectedWidth}x{$expectedHeight}px.";
                }
            }
        }

        foreach (self::REQUIRED_FAVICON_BUNDLE_FILES as $requiredFile) {
            if (!in_array($requiredFile, $uploadedNames, true)) {
                $errors['favicon_bundle'][] = "Missing required favicon bundle file: {$requiredFile}.";
            }
        }

        return $errors;
    }

    private function storeFaviconBundle(array $files): array
    {
        $directory = 'theme/branding/favicon-set-' . uniqid();
        Storage::disk('public')->makeDirectory($directory);

        foreach ($files as $file) {
            $fileName = strtolower($file->getClientOriginalName());
            $this->storeUploadedFileAs($file, $directory, $fileName);
        }

        return [
            'favicon_url' => $directory . '/favicon.ico',
            'favicon_manifest_url' => Storage::disk('public')->exists($directory . '/site.webmanifest')
                ? $directory . '/site.webmanifest'
                : null,
        ];
    }

    private function storeSingleFaviconSet(UploadedFile $file): string
    {
        $directory = 'theme/branding/favicon-set-' . uniqid();
        Storage::disk('public')->makeDirectory($directory);

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = $extension === 'ico' ? 'favicon.ico' : "favicon-source.{$extension}";

        return $this->storeUploadedFileAs($file, $directory, $fileName);
    }

    private function deleteFaviconAssetSet(array $settings): void
    {
        $faviconPath = $settings['favicon_url'] ?? null;
        $manifestPath = $settings['favicon_manifest_url'] ?? null;

        $this->deleteStoredFile($faviconPath);
        $this->deleteStoredFile($manifestPath);
        $this->deleteGeneratedFaviconVersions($faviconPath);

        if (!$faviconPath) {
            return;
        }

        $directory = dirname($faviconPath);
        if ($directory !== '.' && Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->deleteDirectory($directory);
        }
    }

    private function syncPublicRootFavicon(?string $storedFaviconPath): void
    {
        if (!$storedFaviconPath || !Storage::disk('public')->exists($storedFaviconPath)) {
            return;
        }

        if (strtolower(pathinfo($storedFaviconPath, PATHINFO_EXTENSION)) !== 'ico') {
            return;
        }

        File::copy(
            Storage::disk('public')->path($storedFaviconPath),
            public_path('favicon.ico')
        );
    }

    private function restorePublicRootFavicon(): void
    {
        $defaultRootFavicon = public_path('favicon/favicon.ico');

        if (File::exists($defaultRootFavicon)) {
            File::copy($defaultRootFavicon, public_path('favicon.ico'));
        }
    }

    private function updateTailwindThemeConfig(array $fontFamilies): void
    {
        $tailwindThemePath = base_path('tailwind-theme.json');
        $themeConfig = ['fontFamilies' => $fontFamilies];

        try {
            File::put($tailwindThemePath, json_encode($themeConfig, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::error('Failed to update tailwind-theme.json: ' . $e->getMessage());
        }
    }
}
