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
        $attemptId = (string) Str::uuid();
        $normalizedUrl = $this->normalizeUrl($url);
        if (!$normalizedUrl) {
            Log::warning('Screenshot capture skipped because the URL is invalid.', [
                'attempt_id' => $attemptId,
                'url' => $url,
                'directory' => $directory,
                'requested_filename' => $filename,
            ]);

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
        $context = $this->buildLogContext(
            $attemptId,
            $url,
            $normalizedUrl,
            $directory,
            $filename,
            $relativePath,
            $absolutePath
        );

        Log::info('Screenshot capture started.', $context);

        $programmaticStartedAt = microtime(true);
        try {
            $this->captureProgrammatically($normalizedUrl, $absolutePath);
            $programmaticDurationMs = $this->elapsedMilliseconds($programmaticStartedAt);
            $savedFileInfo = $this->savedFileContext($absolutePath, $relativePath);

            Log::info('Programmatic screenshot capture succeeded.', array_merge(
                $context,
                [
                    'duration_ms' => $programmaticDurationMs,
                    'driver' => 'browsershot',
                ],
                $savedFileInfo
            ));

            return $relativePath;
        } catch (\Throwable $exception) {
            Log::warning('Programmatic screenshot capture failed, falling back to thum.io.', array_merge(
                $context,
                [
                    'duration_ms' => $this->elapsedMilliseconds($programmaticStartedAt),
                    'driver' => 'browsershot',
                    'exception_class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'node_binary' => $this->resolvedNodeBinary(),
                    'node_module_path' => $this->resolvedNodeModulePath(),
                ]
            ));
        }

        $fallbackStartedAt = microtime(true);
        Log::info('Fallback screenshot download started.', array_merge($context, [
            'driver' => 'thum_io',
            'fallback_url' => $this->fallbackUrl($normalizedUrl),
        ]));

        try {
            $imageContents = $this->downloadFallbackScreenshot($normalizedUrl);
            if ($imageContents === null) {
                Log::warning('Fallback screenshot capture ended without a saved image.', array_merge($context, [
                    'duration_ms' => $this->elapsedMilliseconds($fallbackStartedAt),
                    'driver' => 'thum_io',
                ]));

                return null;
            }

            $disk->put($relativePath, $imageContents);

            Log::info('Fallback screenshot capture succeeded.', array_merge(
                $context,
                [
                    'duration_ms' => $this->elapsedMilliseconds($fallbackStartedAt),
                    'driver' => 'thum_io',
                ],
                $this->savedFileContext($absolutePath, $relativePath)
            ));

            return $relativePath;
        } catch (\Throwable $exception) {
            Log::error('Fallback screenshot capture failed.', array_merge(
                $context,
                [
                    'duration_ms' => $this->elapsedMilliseconds($fallbackStartedAt),
                    'driver' => 'thum_io',
                    'fallback_url' => $this->fallbackUrl($normalizedUrl),
                    'exception_class' => $exception::class,
                    'message' => $exception->getMessage(),
                ]
            ));

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

        Log::info('Screenshot async capture requested.', [
            'url' => $url,
            'save_path' => $savePath,
            'directory' => $directory !== '' ? $directory : self::DEFAULT_DIRECTORY,
            'filename' => $filename !== '' ? $filename : null,
        ]);

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
                'headers' => $response->headers(),
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

    protected function buildLogContext(
        string $attemptId,
        string $originalUrl,
        string $normalizedUrl,
        string $directory,
        string $filename,
        string $relativePath,
        string $absolutePath
    ): array {
        return [
            'attempt_id' => $attemptId,
            'original_url' => $originalUrl,
            'normalized_url' => $normalizedUrl,
            'directory' => $directory,
            'filename' => $filename,
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
            'public_url' => asset('storage/' . $relativePath),
            'app_env' => app()->environment(),
            'queue_connection' => config('queue.default'),
            'filesystem_disk' => 'public',
        ];
    }

    protected function savedFileContext(string $absolutePath, string $relativePath): array
    {
        clearstatcache(true, $absolutePath);

        return [
            'saved_file_exists' => is_file($absolutePath),
            'saved_file_size' => is_file($absolutePath) ? filesize($absolutePath) : null,
            'saved_relative_path' => $relativePath,
            'saved_absolute_path' => $absolutePath,
        ];
    }

    protected function elapsedMilliseconds(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    protected function resolvedNodeBinary(): ?string
    {
        return $this->resolveExistingPath([
            env('BROWSERSHOT_NODE_BINARY'),
            '/opt/homebrew/bin/node',
            '/usr/local/bin/node',
            '/usr/bin/node',
        ]);
    }

    protected function resolvedNodeModulePath(): ?string
    {
        $nodeModulePath = base_path('node_modules');

        return is_dir($nodeModulePath) ? $nodeModulePath : null;
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
