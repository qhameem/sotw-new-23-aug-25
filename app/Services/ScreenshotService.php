<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScreenshotService
{
    /**
     * The path to the node binary.
     * Calculated from 'which node' on the user's system.
     */
    protected string $nodePath = '/Users/quazihameemmahmud/Library/Application Support/Herd/config/nvm/versions/node/v22.21.1/bin/node';

    /**
     * The path to the npm binary.
     * Calculated from 'which npm' on the user's system.
     */
    protected string $npmPath = '/Users/quazihameemmahmud/Library/Application Support/Herd/config/nvm/versions/node/v22.21.1/bin/npm';

    /**
     * Capture a screenshot of the given URL.
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

            $savePath = $disk->path('screenshots/' . $filename);

            Browsershot::url($url)
                ->setNodeBinary($this->nodePath)
                ->setNpmBinary($this->npmPath)
                ->windowSize(1280, 800)
                ->timeout(60) // Increase Puppeteer navigation timeout to 60s
                ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                ->setOption('waitUntil', 'networkidle2') // Use networkidle2 instead of networkidle0 for modern sites
                ->setDelay(3000) // Extra buffer for dynamic logic/animations
                ->setScreenshotType('jpeg', 85)
                ->save($savePath);

            return asset('storage/screenshots/' . $filename);
        } catch (\Exception $e) {
            Log::error('Browsershot failed to capture screenshot for ' . $url . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Background capture (returns early, doesn't wait for the heavy process).
     * Useful for things like background jobs where we don't return the URL immediately.
     */
    public function captureAsync(string $url, string $savePath): void
    {
        // This is still technically synchronous in PHP execution, 
        // but used within a Queue Job context.
        $this->capture($url);
    }
}
