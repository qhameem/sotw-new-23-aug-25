<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
    private string $settingsPath;

    public function __construct()
    {
        $this->settingsPath = storage_path('app/theme_settings.json');
    }

    public function edit()
    {
        $settings = $this->loadSettings();

        $currentFontUrl = $settings['font_url'] ?? Config::get('theme.font_url');
        $currentFontFamily = $settings['font_family'] ?? Config::get('theme.font_family');
        $availableFonts = $settings['font_families'] ?? [];
        $currentPrimaryColor = $settings['primary_color'] ?? Config::get('theme.primary_color');
        $currentLogoUrl = isset($settings['logo_url']) && $settings['logo_url'] ? Storage::url($settings['logo_url']) : null;
        $currentLogoAltText = $settings['logo_alt_text'] ?? '';
        $currentFaviconUrl = isset($settings['favicon_url']) && $settings['favicon_url'] ? Storage::url($settings['favicon_url']) : null;
        $currentPrimaryButtonTextColor = $settings['primary_button_text_color'] ?? null;
        $currentSubmissionBgUrl = isset($settings['submission_bg_url']) && $settings['submission_bg_url'] ? Storage::url($settings['submission_bg_url']) : null;

        // Define default/placeholder URLs if needed, e.g., for the view's x-data
        $defaultLogoUrl = ''; // e.g., asset('images/default-logo.png');
        $defaultFaviconUrl = ''; // e.g., asset('images/default-favicon.ico');
        $defaultSubmissionBgUrl = asset('images/submission-pattern.png');

        return view('admin.theme.edit', compact(
            'currentFontUrl',
            'currentFontFamily',
            'availableFonts',
            'currentPrimaryColor',
            'currentLogoUrl',
            'currentLogoAltText',
            'currentFaviconUrl',
            'defaultLogoUrl',
            'defaultFaviconUrl',
            'currentPrimaryButtonTextColor',
            'currentSubmissionBgUrl',
            'defaultSubmissionBgUrl'
        ));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'font_url' => [
                'required',
                'url',
                function ($attribute, $value, $fail) {
                    if (!Str::startsWith($value, 'https://fonts.googleapis.com/css')) {
                        $fail('The ' . $attribute . ' must be a valid Google Fonts API URL.');
                    }
                },
            ],
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
            // Removed 'image' rule to better support SVG. Mimes rule will handle type.
            'site_logo' => 'nullable|file|mimes:svg,png,jpg,jpeg,gif|max:2048',
            'logo_alt_text' => 'nullable|string|max:255',
            'remove_logo' => 'sometimes|boolean',
            'site_favicon' => 'nullable|file|mimes:ico,png,svg|max:100', // Max 100KB
            'remove_favicon' => 'sometimes|boolean',
            'primary_button_text_color' => [
                'nullable',
                'string',
                'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'
            ],
            'submission_bg' => 'nullable|file|mimes:svg,png,jpg,jpeg,webp|max:5120', // Max 5MB
            'remove_submission_bg' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.theme.edit')
                ->withErrors($validator)
                ->withInput();
        }

        $settings = $this->loadSettings();

        // Font settings
        $fontUrl = $request->input('font_url');
        $fontFamilies = $this->extractFontFamiliesFromUrl($fontUrl);

        if (empty($fontFamilies) && $fontUrl) { // only error if URL was provided but family extraction failed
            return redirect()->route('admin.theme.edit')
                ->withErrors(['font_url' => 'Could not automatically extract font family name. Ensure a valid Google Font URL.'])
                ->withInput();
        }

        $settings['font_url'] = $fontUrl;
        $settings['font_families'] = $fontFamilies;

        // New logic for default font
        $defaultFont = $request->input('default_font_family');
        if (!empty($fontFamilies)) {
            if ($defaultFont && in_array($defaultFont, $fontFamilies)) {
                $settings['font_family'] = $defaultFont;
            } else {
                // If no default is selected, or the selected one is invalid, default to the first one.
                $settings['font_family'] = $fontFamilies[0];
            }
        } else {
            $settings['font_family'] = null;
        }
        $settings['primary_color'] = $request->input('primary_color');
        $settings['primary_button_text_color'] = $request->input('primary_button_text_color');

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
            $this->deleteStoredFile($settings['favicon_url'] ?? null);
            $settings['favicon_url'] = null;
        } elseif ($request->hasFile('site_favicon')) {
            $this->deleteStoredFile($settings['favicon_url'] ?? null);
            // Also delete previously generated versions if a new favicon is uploaded
            $this->deleteGeneratedFaviconVersions($settings['favicon_url'] ?? null);

            $uploadedFaviconFile = $request->file('site_favicon');
            $originalFaviconPath = $this->storeUploadedFile($uploadedFaviconFile, 'favicon', 'theme/branding');
            $settings['favicon_url'] = $originalFaviconPath;

            // Generate different sizes if it's a PNG
            if ($uploadedFaviconFile->getClientOriginalExtension() === 'png') {
                try {
                    $this->generateFaviconVersions($uploadedFaviconFile, $originalFaviconPath);
                } catch (\Exception $e) {
                    Log::error('Failed to generate favicon versions: ' . $e->getMessage());
                    // Optionally, add a non-blocking error to the session for the user
                    // session()->flash('warning', 'Original favicon saved, but failed to generate additional sizes. Please check logs.');
                }
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

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('config:cache');

            return redirect()->route('admin.theme.edit')->with('success', 'Theme settings updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update theme settings: ' . $e->getMessage());
            return redirect()->route('admin.theme.edit')
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
                'primary_color' => Config::get('theme.primary_color', '#3b82f6'),
                'primary_button_text_color' => Config::get('theme.primary_button_text_color'), // Added
                'logo_url' => null,
                'logo_alt_text' => null,
                'favicon_url' => null,
                'submission_bg_url' => null,
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

    private function deleteStoredFile(?string $filePath): void
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    private function extractFontFamiliesFromUrl(string $url = null): array
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
        ];

        foreach ($generatedFiles as $filePath) {
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
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
