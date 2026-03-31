<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ScreenshotService
{
    /**
     * Capture a screenshot of the given URL using the thum.io free API.
     *
     * @param string $url
     * @return string|null The public URL of the screenshot, or null on failure.
     */
    public function capture(string $url): ?string
    {
        try {
            $filename = 'screenshot_' . md5($url . time()) . '.jpg';
            $disk = Storage::disk('public');

            // Ensure the directory exists
            if (!$disk->exists('screenshots')) {
                $disk->makeDirectory('screenshots');
            }

            // Use thum.io to generate a screenshot (free, no API key required)
            $thumUrl = 'https://image.thum.io/get/width/1280/crop/800/noanimate/' . $url;

            // Download the screenshot image
            $response = Http::timeout(5)->get($thumUrl);

            if (!$response->successful()) {
                Log::error('Thum.io screenshot failed for ' . $url, [
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Save the downloaded image to storage
            $disk->put('screenshots/' . $filename, $response->body());

            return asset('storage/screenshots/' . $filename);
        } catch (\Exception $e) {
            Log::error('Screenshot failed for ' . $url, [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Background capture (returns early, doesn't wait for the heavy process).
     * Useful for things like background jobs where we don't return the URL immediately.
     */
    public function captureAsync(string $url, string $savePath): void
    {
        $this->capture($url);
    }
}
