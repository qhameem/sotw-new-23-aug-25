<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SiteManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $customFaviconPath = config('theme.favicon_url');
        $icons = $customFaviconPath
            ? $this->buildCustomIcons($customFaviconPath)
            : [];

        if ($icons === []) {
            $icons = [
                [
                    'src' => asset('favicon/android-chrome-192x192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => asset('favicon/android-chrome-512x512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
                [
                    'src' => asset('favicon/apple-touch-icon.png'),
                    'sizes' => '180x180',
                    'type' => 'image/png',
                ],
            ];
        }

        return response()->json([
            'name' => config('app.name', 'Software on the Web'),
            'short_name' => config('app.name', 'Software on the Web'),
            'icons' => array_values($icons),
            'theme_color' => config('theme.body_bg_color', '#ffffff'),
            'background_color' => config('theme.body_bg_color', '#ffffff'),
            'display' => 'standalone',
            'start_url' => url('/'),
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function buildCustomIcons(string $customFaviconPath): array
    {
        $directory = dirname($customFaviconPath);
        $paths = [
            $directory . '/apple-touch-icon.png',
            $directory . '/favicon-32x32.png',
            $directory . '/favicon-16x16.png',
            $customFaviconPath,
        ];

        $icons = [];

        foreach ($paths as $path) {
            $icon = $this->buildIconMetadata($path);

            if (!$icon) {
                continue;
            }

            $icons[$icon['src']] = $icon;
        }

        return $icons;
    }

    private function buildIconMetadata(string $path): ?array
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $icon = [
            'src' => url($disk->url($path)) . '?v=' . $disk->lastModified($path),
            'type' => $this->resolveMimeType($extension),
        ];

        if ($extension === 'svg') {
            $icon['sizes'] = 'any';

            return $icon;
        }

        $dimensions = @getimagesize($disk->path($path));

        if ($dimensions && isset($dimensions[0], $dimensions[1])) {
            $icon['sizes'] = $dimensions[0] . 'x' . $dimensions[1];
        }

        return $icon;
    }

    private function resolveMimeType(string $extension): string
    {
        return match ($extension) {
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
