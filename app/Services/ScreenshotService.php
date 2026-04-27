<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class ScreenshotService
{
    private const DEFAULT_DIRECTORY = 'screenshots';

    private const DEFAULT_EXTENSION = 'jpg';

    /**
     * Capture a screenshot of the given URL and return its public URL.
     */
    public function capture(string $url): ?string
    {
        $relativePath = $this->captureToStorage($url);

        return $relativePath ? asset('storage/' . $relativePath) : null;
    }

    /**
     * Capture a screenshot and store it on the public disk.
     *
     * @return string|null Relative storage path or null on failure.
     */
    public function captureToStorage(
        string $url,
        string $directory = self::DEFAULT_DIRECTORY,
        ?string $filename = null
    ): ?string {
        $normalizedUrl = $this->normalizeUrl($url);
        if (!$normalizedUrl) {
            Log::warning('Screenshot skipped because the URL is invalid.', ['url' => $url]);

            return null;
        }

        $directory = trim($directory, '/');
        $filename = $this->normalizeFilename($filename ?? $this->makeFilename($normalizedUrl));
        $relativePath = $directory . '/' . $filename;

        $disk = Storage::disk('public');
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $absolutePath = $disk->path($relativePath);

        try {
            $this->captureProgrammatically($normalizedUrl, $absolutePath);

            return $relativePath;
        } catch (\Throwable $exception) {
            Log::warning('Programmatic screenshot capture failed, falling back to thum.io.', [
                'url' => $normalizedUrl,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $imageContents = $this->downloadFallbackScreenshot($normalizedUrl);
            if ($imageContents === null) {
                return null;
            }

            $disk->put($relativePath, $imageContents);

            return $relativePath;
        } catch (\Throwable $exception) {
            Log::error('Fallback screenshot capture failed.', [
                'url' => $normalizedUrl,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Background capture helper for legacy callers.
     */
    public function captureAsync(string $url, string $savePath): void
    {
        $savePath = trim($savePath, '/');
        $directory = trim(dirname($savePath), './');
        $filename = basename($savePath);

        $this->captureToStorage(
            $url,
            $directory !== '' ? $directory : self::DEFAULT_DIRECTORY,
            $filename !== '' ? $filename : null
        );
    }

    protected function captureProgrammatically(string $url, string $absolutePath): void
    {
        $browsershot = Browsershot::url($url)
            ->windowSize(1440, 900)
            ->deviceScaleFactor(1)
            ->waitForSelector('body', ['timeout' => 10000])
            ->waitUntilNetworkIdle(false)
            ->delay(1500)
            ->timeout(45)
            ->newHeadless()
            ->noSandbox()
            ->dismissDialogs()
            ->ignoreHttpsErrors()
            ->preventUnsuccessfulResponse()
            ->userAgent($this->userAgent())
            ->addChromiumArguments([
                'disable-dev-shm-usage',
                'hide-scrollbars',
            ]);

        $nodeModulePath = base_path('node_modules');
        if (is_dir($nodeModulePath)) {
            $browsershot->setNodeModulePath($nodeModulePath);
        }

        $nodeBinary = $this->resolveExistingPath([
            env('BROWSERSHOT_NODE_BINARY'),
            '/opt/homebrew/bin/node',
            '/usr/local/bin/node',
            '/usr/bin/node',
        ]);

        if ($nodeBinary) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        $browsershot->save($absolutePath);
    }

    protected function downloadFallbackScreenshot(string $url): ?string
    {
        $response = Http::timeout(20)
            ->accept('image/jpeg,image/png,image/*')
            ->get($this->fallbackUrl($url));

        if (!$response->successful()) {
            Log::warning('thum.io fallback screenshot request was unsuccessful.', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (!str_starts_with($contentType, 'image/')) {
            Log::warning('thum.io fallback did not return an image.', [
                'url' => $url,
                'content_type' => $contentType,
            ]);

            return null;
        }

        $body = $response->body();
        if ($body === '') {
            Log::warning('thum.io fallback returned an empty body.', ['url' => $url]);

            return null;
        }

        return $body;
    }

    protected function fallbackUrl(string $url): string
    {
        return 'https://image.thum.io/get/width/1440/crop/900/allowJPG/noanimate/?url=' . urlencode($url);
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    protected function makeFilename(string $url): string
    {
        return 'screenshot_' . md5($url . microtime(true)) . '.' . self::DEFAULT_EXTENSION;
    }

    protected function normalizeFilename(string $filename): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            return $this->makeFilename(Str::uuid()->toString());
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '') {
            $filename .= '.' . self::DEFAULT_EXTENSION;
        }

        return $filename;
    }

    protected function userAgent(): string
    {
        return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
    }

    protected function resolveExistingPath(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
