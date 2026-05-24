<?php

namespace App\Services;

use App\Support\PublicUrlGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ProductLogoStorageService
{
    public const DIRECTORY = 'logos';
    public const TARGET_WIDTH = 100;
    public const TARGET_HEIGHT = 100;
    public const WEBP_QUALITY = 80;

    private const MAX_REMOTE_BYTES = 5242880;

    public function storeUploadedFile(UploadedFile $file): string
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the uploaded logo file.');
        }

        return $this->storeBinary(
            $contents,
            $file->getClientOriginalExtension(),
            $file->getMimeType()
        );
    }

    public function storeDataUrl(string $dataUrl): ?string
    {
        if (!preg_match('/^data:image\/([\w.+-]+);base64,(.+)$/s', $dataUrl, $matches)) {
            return null;
        }

        $contents = base64_decode($matches[2], true);

        if ($contents === false) {
            return null;
        }

        return $this->storeBinary(
            $contents,
            $this->normalizeExtension($matches[1]),
            'image/' . $matches[1]
        );
    }

    public function storeRemoteUrl(string $url): ?string
    {
        $sanitizedUrl = PublicUrlGuard::sanitizePublicHttpUrl($url);

        $response = Http::timeout(15)
            ->accept('image/*')
            ->withHeaders([
                'User-Agent' => 'Software on the Web Logo Fetcher',
            ])
            ->get($sanitizedUrl);

        if (!$response->successful()) {
            return null;
        }

        $contents = $response->body();

        if ($contents === '' || strlen($contents) > self::MAX_REMOTE_BYTES) {
            return null;
        }

        $mimeType = trim((string) Str::before((string) $response->header('Content-Type'), ';'));
        $extension = $this->extensionFromMime($mimeType)
            ?? $this->normalizeExtension(pathinfo((string) parse_url($sanitizedUrl, PHP_URL_PATH), PATHINFO_EXTENSION));

        return $this->storeBinary($contents, $extension, $mimeType);
    }

    public function storePublicDiskPath(string $path): ?string
    {
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);

        return $this->storeBinary(
            $contents,
            pathinfo($path, PATHINFO_EXTENSION),
            $this->guessMimeType($path)
        );
    }

    private function storeBinary(string $contents, ?string $extension = null, ?string $mimeType = null): string
    {
        $normalizedExtension = $this->normalizeExtension($extension);

        if ($this->isSvg($normalizedExtension, $mimeType, $contents)) {
            $path = self::DIRECTORY . '/' . Str::uuid() . '.svg';
            Storage::disk('public')->put($path, $contents);

            return $path;
        }

        try {
            $image = $this->imageManager()->read($contents);
            $image->contain(self::TARGET_WIDTH, self::TARGET_HEIGHT, background: 'transparent');

            $path = self::DIRECTORY . '/' . Str::uuid() . '.webp';
            Storage::disk('public')->put(
                $path,
                (string) $image->encode(new WebpEncoder(self::WEBP_QUALITY, strip: true))
            );

            return $path;
        } catch (\Throwable $throwable) {
            Log::warning('Product logo optimization skipped; storing original asset instead.', [
                'extension' => $normalizedExtension,
                'mime_type' => $mimeType,
                'error' => $throwable->getMessage(),
            ]);

            $fallbackExtension = $normalizedExtension ?: 'bin';
            $path = self::DIRECTORY . '/' . Str::uuid() . '.' . $fallbackExtension;
            Storage::disk('public')->put($path, $contents);

            return $path;
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

    private function isSvg(?string $extension, ?string $mimeType, string $contents): bool
    {
        if ($extension === 'svg') {
            return true;
        }

        if (is_string($mimeType) && str_contains(strtolower($mimeType), 'svg')) {
            return true;
        }

        return str_contains(strtolower(substr($contents, 0, 512)), '<svg');
    }

    private function normalizeExtension(?string $extension): ?string
    {
        if (!is_string($extension)) {
            return null;
        }

        $normalized = strtolower(trim($extension, ". \t\n\r\0\x0B"));

        return match ($normalized) {
            'jpeg' => 'jpg',
            'svg+xml' => 'svg',
            'x-icon', 'vnd.microsoft.icon' => 'ico',
            '' => null,
            default => $normalized,
        };
    }

    private function extensionFromMime(?string $mimeType): ?string
    {
        return match (strtolower((string) $mimeType)) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
            'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
            default => null,
        };
    }

    private function guessMimeType(string $path): ?string
    {
        $absolutePath = Storage::disk('public')->path($path);
        $mimeType = @mime_content_type($absolutePath);

        return is_string($mimeType) && $mimeType !== '' ? $mimeType : null;
    }
}
