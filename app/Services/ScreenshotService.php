<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Symfony\Component\Process\Process;
use App\Support\ProductMediaSeo;

class ScreenshotService
{
    private const DEFAULT_DIRECTORY = 'screenshots';

    private const DEFAULT_EXTENSION = 'webp';

    private const PROVIDER_USAGE_PREFIX = 'screenshot_provider_usage';

    private const PROVIDER_ROTATION_PREFIX = 'screenshot_provider_rotation';

    /**
     * Capture a screenshot of the given URL and return its public URL.
     */
    public function capture(string $url): ?string
    {
        $relativePath = $this->captureToStorage($url);

        return $relativePath ? asset('storage/' . $relativePath) : null;
    }

    /**
     * Provide admin/debug visibility into provider quotas and weighted order.
     */
    public function providerDashboard(): array
    {
        $snapshots = $this->configuredProviderSnapshots();
        $availableSnapshots = array_values(array_filter(
            $snapshots,
            fn (array $snapshot): bool => $snapshot['configured'] && $snapshot['remaining'] > 0
        ));

        return [
            'configured_providers' => $this->configuredProviders(),
            'snapshots' => $snapshots,
            'available_provider_order' => $this->orderedProvidersForDashboard($availableSnapshots),
        ];
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
            $this->logEvent('warning', 'Screenshot capture skipped because the URL is invalid.', [
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

        $this->logEvent('info', 'Screenshot capture started.', $context);

        $providers = $this->orderedAvailableProviders();
        if ($providers !== []) {
            $providerStartedAt = microtime(true);

            $this->logEvent('info', 'Weighted screenshot provider rotation resolved.', array_merge($context, [
                'provider_order' => $providers,
                'provider_quotas' => $this->configuredProviderSnapshots(),
            ]));

            foreach ($providers as $provider) {
                $attemptStartedAt = microtime(true);

                try {
                    $imageContents = $this->downloadProviderScreenshot($provider, $normalizedUrl);
                    $disk->put($relativePath, $imageContents);

                    $usage = $this->incrementProviderUsage($provider);

                    $this->logEvent('info', 'Programmatic screenshot capture succeeded.', array_merge(
                        $context,
                        [
                            'duration_ms' => $this->elapsedMilliseconds($providerStartedAt),
                            'provider_attempt_duration_ms' => $this->elapsedMilliseconds($attemptStartedAt),
                            'driver' => $provider,
                            'provider_order' => $providers,
                            'provider_usage' => $usage,
                        ],
                        $this->savedFileContext($absolutePath, $relativePath)
                    ));

                    return $relativePath;
                } catch (\Throwable $exception) {
                    $this->logEvent('warning', 'Screenshot provider attempt failed.', array_merge(
                        $context,
                        [
                            'duration_ms' => $this->elapsedMilliseconds($attemptStartedAt),
                            'driver' => $provider,
                            'exception_class' => $exception::class,
                            'message' => $exception->getMessage(),
                        ]
                    ));
                }
            }

            $this->logEvent('warning', 'All configured screenshot providers failed for this attempt.', array_merge($context, [
                'duration_ms' => $this->elapsedMilliseconds($providerStartedAt),
                'provider_order' => $providers,
            ]));
        } else {
            $this->logEvent('warning', 'No screenshot providers are currently available for this attempt.', array_merge($context, [
                'provider_quotas' => $this->configuredProviderSnapshots(),
            ]));
        }

        $localStartedAt = microtime(true);
        try {
            $this->captureProgrammatically($normalizedUrl, $absolutePath);

            $this->logEvent('info', 'Local Chrome screenshot capture succeeded.', array_merge(
                $context,
                [
                    'duration_ms' => $this->elapsedMilliseconds($localStartedAt),
                    'driver' => 'local_chrome',
                ],
                $this->savedFileContext($absolutePath, $relativePath)
            ));

            return $relativePath;
        } catch (\Throwable $exception) {
            $this->logEvent('error', 'Local Chrome screenshot capture failed after provider attempts were exhausted.', array_merge(
                $context,
                [
                    'duration_ms' => $this->elapsedMilliseconds($localStartedAt),
                    'driver' => 'local_chrome',
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

        $this->logEvent('info', 'Screenshot async capture requested.', [
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
        $chromePath = $this->resolvedChromePath();
        if (!$chromePath) {
            throw new \RuntimeException('Chrome executable path could not be resolved.');
        }

        $userDataDir = $this->resolvedUserDataDir();
        $tempPath = $this->resolvedTempPath();

        $command = [
            $chromePath,
            '--headless=new',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--hide-scrollbars',
            '--disable-setuid-sandbox',
            '--no-zygote',
            '--no-sandbox',
            '--ignore-certificate-errors',
            '--window-size=' . $this->viewportWidth() . ',' . $this->viewportHeight(),
            '--screenshot=' . $absolutePath,
            $url,
        ];

        if ($userDataDir) {
            $command[] = '--user-data-dir=' . $userDataDir;
        }

        $process = new Process($command, public_path(), $this->resolvedChromeEnvironment($tempPath), null, 60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'Chrome CLI screenshot command failed.');
        }

        if (!is_file($absolutePath) || filesize($absolutePath) === 0) {
            throw new \RuntimeException('Chrome CLI completed without producing a screenshot file.');
        }

        $normalizedContents = $this->normalizeImageContents((string) file_get_contents($absolutePath));
        file_put_contents($absolutePath, $normalizedContents);
    }

    protected function downloadProviderScreenshot(string $provider, string $url): string
    {
        return match ($provider) {
            'apiflash' => $this->downloadFromApiFlash($url),
            'screenshotone' => $this->downloadFromScreenshotOne($url),
            'snaprender' => $this->downloadFromSnapRender($url),
            'microlink' => $this->downloadFromMicrolink($url),
            'screenshotbase' => $this->downloadFromScreenshotBase($url),
            default => throw new \InvalidArgumentException("Unsupported screenshot provider [{$provider}]."),
        };
    }

    protected function downloadFromApiFlash(string $url): string
    {
        $response = $this->providerHttpClient()->get(
            (string) $this->providerConfig('apiflash', 'base_url'),
            [
                'access_key' => $this->providerConfig('apiflash', 'access_key'),
                'url' => $url,
                'format' => 'webp',
                'quality' => 85,
                'response_type' => 'image',
                'viewport' => $this->viewportWidth() . 'x' . $this->viewportHeight(),
                'delay' => 2,
                'fresh' => 'true',
            ]
        );

        return $this->imageContentsFromResponse('apiflash', $response);
    }

    protected function downloadFromScreenshotOne(string $url): string
    {
        $response = $this->providerHttpClient()->get(
            (string) $this->providerConfig('screenshotone', 'base_url'),
            [
                'access_key' => $this->providerConfig('screenshotone', 'access_key'),
                'url' => $url,
                'format' => 'webp',
                'image_quality' => 85,
                'viewport_width' => $this->viewportWidth(),
                'viewport_height' => $this->viewportHeight(),
                'block_cookie_banners' => 'true',
                'block_ads' => 'true',
                'block_chats' => 'true',
                'delay' => 2,
            ]
        );

        return $this->imageContentsFromResponse('screenshotone', $response);
    }

    protected function downloadFromSnapRender(string $url): string
    {
        $response = $this->providerHttpClient()
            ->withHeaders([
                'X-API-Key' => (string) $this->providerConfig('snaprender', 'api_key'),
            ])
            ->get(
                (string) $this->providerConfig('snaprender', 'base_url'),
                [
                    'url' => $url,
                    'format' => 'webp',
                    'quality' => 85,
                    'width' => $this->viewportWidth(),
                    'height' => $this->viewportHeight(),
                    'block_ads' => 'true',
                    'block_cookie_banners' => 'true',
                ]
            );

        return $this->imageContentsFromResponse('snaprender', $response);
    }

    protected function downloadFromMicrolink(string $url): string
    {
        $baseUrl = $this->microlinkImageBaseUrl();
        $request = $this->providerHttpClient();
        $apiKey = (string) $this->providerConfig('microlink', 'api_key');

        if ($apiKey !== '') {
            $request = $request->withHeaders([
                'x-api-key' => $apiKey,
            ]);
        }

        $response = $request->get($baseUrl, [
            'url' => $url,
            'width' => $this->viewportWidth(),
            'height' => $this->viewportHeight(),
        ]);

        return $this->imageContentsFromResponse('microlink', $response);
    }

    protected function downloadFromScreenshotBase(string $url): string
    {
        $response = $this->providerHttpClient()
            ->withHeaders([
                'apikey' => (string) $this->providerConfig('screenshotbase', 'api_key'),
            ])
            ->get(
                rtrim((string) $this->providerConfig('screenshotbase', 'base_url'), '/') . '/take',
                [
                    'url' => $url,
                    'format' => 'webp',
                    'quality' => 85,
                    'viewport_width' => $this->viewportWidth(),
                    'viewport_height' => $this->viewportHeight(),
                    'delay' => 2,
                ]
            );

        return $this->imageContentsFromResponse('screenshotbase', $response);
    }

    protected function imageContentsFromResponse(string $provider, $response): string
    {
        if (!$response->successful()) {
            if (in_array($response->status(), [402, 429], true)) {
                $this->markProviderExhausted($provider);
            }

            $body = trim(Str::limit(strip_tags($response->body()), 500));

            throw new \RuntimeException("{$provider} request failed with status {$response->status()}: {$body}");
        }

        $body = $response->body();
        if ($body === '') {
            throw new \RuntimeException("{$provider} returned an empty response body.");
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (!str_starts_with($contentType, 'image/') && !$this->looksLikeImageBinary($body)) {
            throw new \RuntimeException("{$provider} returned a non-image response type [{$contentType}].");
        }

        return $this->normalizeImageContents($body);
    }

    protected function normalizeImageContents(string $body): string
    {
        $manager = new ImageManager(new Driver());

        return (string) $manager->read($body)->toWebp(85);
    }

    protected function looksLikeImageBinary(string $body): bool
    {
        return Str::startsWith($body, [
            "\xFF\xD8\xFF",
            "\x89PNG",
            'GIF8',
            'RIFF',
        ]);
    }

    protected function orderedAvailableProviders(): array
    {
        $snapshots = $this->availableProviderSnapshots();
        return $this->orderedProvidersForDashboard($snapshots);
    }

    protected function orderedProvidersForDashboard(array $snapshots): array
    {
        if ($snapshots === []) {
            return [];
        }

        $sequence = $this->buildWeightedProviderSequence($snapshots);
        if ($sequence === []) {
            return [];
        }

        $rotationKey = $this->providerRotationKey($snapshots);
        $startIndex = (int) Cache::get($rotationKey, 0);
        Cache::put(
            $rotationKey,
            ($startIndex + 1) % count($sequence),
            $this->latestResetAt($snapshots)
        );

        return $this->distinctProvidersFromSequence($sequence, $startIndex);
    }

    protected function availableProviderSnapshots(): array
    {
        return array_values(array_filter(
            array_map(fn (string $provider): array => $this->providerSnapshot($provider), $this->configuredProviders()),
            fn (array $snapshot): bool => $snapshot['configured'] && $snapshot['remaining'] > 0
        ));
    }

    protected function configuredProviderSnapshots(): array
    {
        return array_map(fn (string $provider): array => $this->providerSnapshot($provider), $this->configuredProviders());
    }

    protected function providerSnapshot(string $provider): array
    {
        $limit = max(0, (int) $this->providerConfig($provider, 'free_limit'));
        $period = $this->normalizedPeriod((string) $this->providerConfig($provider, 'free_period', 'monthly'));
        $used = $limit > 0 ? (int) Cache::get($this->providerUsageKey($provider, $period), 0) : 0;
        $remaining = $limit > 0 ? max(0, $limit - $used) : 0;
        $resetAt = $this->periodResetAt($period);

        return [
            'name' => $provider,
            'configured' => $this->providerIsConfigured($provider),
            'weight' => max(1, (int) $this->providerConfig($provider, 'weight', max(1, $limit))),
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining,
            'period' => $period,
            'period_key' => $this->periodKey($period),
            'reset_at' => $resetAt->toIso8601String(),
        ];
    }

    protected function incrementProviderUsage(string $provider): array
    {
        $period = $this->normalizedPeriod((string) $this->providerConfig($provider, 'free_period', 'monthly'));
        $key = $this->providerUsageKey($provider, $period);
        $expiresAt = $this->periodResetAt($period);
        $limit = max(0, (int) $this->providerConfig($provider, 'free_limit'));

        Cache::add($key, 0, $expiresAt);
        $used = (int) Cache::increment($key);

        return [
            'provider' => $provider,
            'period' => $period,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
            'limit' => $limit,
            'reset_at' => $expiresAt->toIso8601String(),
        ];
    }

    protected function markProviderExhausted(string $provider): void
    {
        $period = $this->normalizedPeriod((string) $this->providerConfig($provider, 'free_period', 'monthly'));
        $limit = max(0, (int) $this->providerConfig($provider, 'free_limit'));

        if ($limit <= 0) {
            return;
        }

        Cache::put(
            $this->providerUsageKey($provider, $period),
            $limit,
            $this->periodResetAt($period)
        );
    }

    protected function providerUsageKey(string $provider, string $period): string
    {
        return implode(':', [
            self::PROVIDER_USAGE_PREFIX,
            $provider,
            $period,
            $this->periodKey($period),
        ]);
    }

    protected function providerRotationKey(array $snapshots): string
    {
        $suffix = array_map(
            fn (array $snapshot): array => [
                'name' => $snapshot['name'],
                'period' => $snapshot['period'],
                'period_key' => $snapshot['period_key'],
            ],
            $snapshots
        );

        return self::PROVIDER_ROTATION_PREFIX . ':' . sha1(json_encode($suffix));
    }

    protected function buildWeightedProviderSequence(array $snapshots): array
    {
        if ($snapshots === []) {
            return [];
        }

        $weights = array_map(
            fn (array $snapshot): int => max(1, (int) $snapshot['weight']),
            $snapshots
        );

        $gcd = array_reduce(
            $weights,
            fn (int $carry, int $weight): int => $carry === 0 ? $weight : $this->greatestCommonDivisor($carry, $weight),
            0
        );

        $gcd = max(1, $gcd);
        $sequence = [];

        foreach ($snapshots as $snapshot) {
            $repetitions = max(1, (int) floor(((int) $snapshot['weight']) / $gcd));
            for ($i = 0; $i < $repetitions; $i++) {
                $sequence[] = $snapshot['name'];
            }
        }

        return $sequence;
    }

    protected function distinctProvidersFromSequence(array $sequence, int $startIndex): array
    {
        if ($sequence === []) {
            return [];
        }

        $ordered = [];
        $seen = [];
        $count = count($sequence);

        for ($offset = 0; $offset < $count; $offset++) {
            $provider = $sequence[($startIndex + $offset) % $count];
            if (!isset($seen[$provider])) {
                $seen[$provider] = true;
                $ordered[] = $provider;
            }
        }

        return $ordered;
    }

    protected function greatestCommonDivisor(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return abs($a);
    }

    protected function configuredProviders(): array
    {
        $providers = config('services.screenshot.providers', []);

        if (is_string($providers)) {
            $providers = explode(',', $providers);
        }

        return array_values(array_filter(array_map(
            static fn ($provider): string => trim(strtolower((string) $provider)),
            is_array($providers) ? $providers : []
        )));
    }

    protected function providerIsConfigured(string $provider): bool
    {
        return match ($provider) {
            'apiflash' => (string) $this->providerConfig($provider, 'access_key') !== '',
            'screenshotone' => (string) $this->providerConfig($provider, 'access_key') !== '',
            'snaprender' => (string) $this->providerConfig($provider, 'api_key') !== '',
            'microlink' => filter_var($this->providerConfig($provider, 'free_no_key', true), FILTER_VALIDATE_BOOL)
                || (string) $this->providerConfig($provider, 'api_key') !== '',
            'screenshotbase' => (string) $this->providerConfig($provider, 'api_key') !== '',
            default => false,
        };
    }

    protected function providerConfig(string $provider, ?string $key = null, mixed $default = null): mixed
    {
        $config = config("services.screenshot.{$provider}", []);
        if (!is_array($config)) {
            return $default;
        }

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    protected function normalizedPeriod(string $period): string
    {
        return strtolower($period) === 'daily' ? 'daily' : 'monthly';
    }

    protected function periodKey(string $period): string
    {
        $now = now()->timezone(config('app.timezone'));

        return $period === 'daily'
            ? $now->format('Y-m-d')
            : $now->format('Y-m');
    }

    protected function periodResetAt(string $period): Carbon
    {
        $now = now()->timezone(config('app.timezone'));

        return $period === 'daily'
            ? $now->copy()->endOfDay()
            : $now->copy()->endOfMonth();
    }

    protected function latestResetAt(array $snapshots): Carbon
    {
        $latest = now()->timezone(config('app.timezone'))->copy()->addDay();

        foreach ($snapshots as $snapshot) {
            $resetAt = Carbon::parse($snapshot['reset_at']);
            if ($resetAt->greaterThan($latest)) {
                $latest = $resetAt;
            }
        }

        return $latest;
    }

    protected function providerHttpClient()
    {
        return Http::timeout((int) config('services.screenshot.timeout', 30))
            ->accept('image/jpeg,image/png,image/webp,image/*')
            ->withUserAgent($this->userAgent());
    }

    protected function microlinkImageBaseUrl(): string
    {
        $configured = rtrim((string) $this->providerConfig('microlink', 'base_url', 'https://api.microlink.io'), '/');

        if (str_contains($configured, 'api.microlink.io')) {
            return 'https://image.microlink.io';
        }

        return $configured;
    }

    protected function viewportWidth(): int
    {
        return max(320, (int) config('services.screenshot.width', 1440));
    }

    protected function viewportHeight(): int
    {
        return max(240, (int) config('services.screenshot.height', 900));
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
        return ProductMediaSeo::screenshotFilenameForUrl($url, self::DEFAULT_EXTENSION);
    }

    protected function normalizeFilename(string $filename): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            return $this->makeFilename(Str::uuid()->toString());
        }

        $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
        $nameWithoutExtension = trim($nameWithoutExtension);

        if ($nameWithoutExtension === '') {
            $nameWithoutExtension = pathinfo($this->makeFilename(Str::uuid()->toString()), PATHINFO_FILENAME);
        }

        return $nameWithoutExtension . '.' . self::DEFAULT_EXTENSION;
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
            'configured_providers' => $this->configuredProviders(),
            'configured_provider_quotas' => $this->configuredProviderSnapshots(),
            'configured_chrome_path' => config('services.screenshot.chrome_path'),
            'configured_puppeteer_home' => config('services.screenshot.home'),
            'configured_puppeteer_cache_dir' => config('services.screenshot.cache_dir'),
            'configured_user_data_dir' => $this->resolvedUserDataDir(),
            'configured_temp_path' => $this->resolvedTempPath(),
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

    protected function resolvedChromePath(): ?string
    {
        return $this->resolveExistingPath([
            config('services.screenshot.chrome_path'),
        ]);
    }

    protected function resolvedChromeEnvironment(?string $tempPath = null): array
    {
        return array_filter([
            'HOME' => config('services.screenshot.home'),
            'PUPPETEER_CACHE_DIR' => config('services.screenshot.cache_dir'),
            'TMPDIR' => $tempPath,
        ], fn ($value) => is_string($value) && $value !== '');
    }

    protected function resolvedUserDataDir(): ?string
    {
        return $this->ensureDirectory(storage_path('app/puppeteer-profile'));
    }

    protected function resolvedTempPath(): ?string
    {
        return $this->ensureDirectory(storage_path('app/browsershot-temp'));
    }

    protected function ensureDirectory(string $path): ?string
    {
        if (is_dir($path)) {
            return $path;
        }

        try {
            if (@mkdir($path, 0775, true) || is_dir($path)) {
                return $path;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
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

    protected function logEvent(string $level, string $message, array $context = []): void
    {
        match ($level) {
            'error' => Log::error($message, $context),
            'warning' => Log::warning($message, $context),
            default => Log::info($message, $context),
        };

        $payload = [
            'timestamp' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        @file_put_contents(
            storage_path('logs/screenshot-debug.log'),
            json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }
}
