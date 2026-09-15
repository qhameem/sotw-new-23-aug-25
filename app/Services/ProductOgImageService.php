<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductOgImageService
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const TEMPLATE_VERSION = 1;

    public function url(Product $product): string
    {
        return route('products.og-image', [
            'product' => $product->slug,
            'v' => $this->version($product),
        ]);
    }

    public function version(Product $product): string
    {
        $latestMediaUpdate = $product->media()->max('updated_at');

        return substr(hash('sha256', implode('|', [
            self::TEMPLATE_VERSION,
            $product->getKey(),
            $product->updated_at?->getTimestamp() ?? 0,
            $latestMediaUpdate ? strtotime((string) $latestMediaUpdate) : 0,
        ])), 0, 16);
    }

    public function generate(Product $product): string
    {
        $version = $this->version($product);
        $directory = 'og_images/products/'.$product->getKey();
        $path = $directory.'/'.$version.'.jpg';
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return (string) $disk->get($path);
        }

        $image = $this->render($product);
        $disk->put($path, $image);
        $stalePaths = collect($disk->files($directory))
            ->filter(fn (string $candidate): bool => $candidate !== $path)
            ->values()
            ->all();
        if ($stalePaths !== []) {
            $disk->delete($stalePaths);
        }

        return $image;
    }

    private function render(Product $product): string
    {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imageantialias($canvas, true);
        $this->paintBackground($canvas);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $muted = imagecolorallocate($canvas, 203, 213, 225);
        $accent = imagecolorallocate($canvas, 99, 102, 241);

        imagefilledrectangle($canvas, 64, 64, 72, 566, $accent);
        $this->drawText($canvas, 'SOFTWARE ON THE WEB', 24, 105, 104, $muted, true);
        $this->drawWrappedText($canvas, Str::limit($product->name, 58, ''), 54, 105, 185, 480, 62, $white, true, 2);

        $tagline = trim((string) ($product->product_page_tagline ?: $product->tagline));
        if ($tagline !== '') {
            $this->drawWrappedText($canvas, Str::limit($tagline, 150, '…'), 27, 105, 350, 480, 38, $muted, false, 3);
        }

        $screenshot = $this->sourceImage($this->primaryScreenshot($product));
        $logo = $this->sourceImage($this->localProductPath($product->logo));

        if ($screenshot) {
            $this->drawCover($canvas, $screenshot, 635, 72, 501, 486, 18);
            imagedestroy($screenshot);
            if ($logo) {
                $this->drawContained($canvas, $logo, 104, 488, 64, 64);
            }
        } elseif ($logo) {
            $panel = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 635, 72, 1136, 558, $panel);
            $this->drawContained($canvas, $logo, 735, 165, 300, 300);
        } else {
            $panel = imagecolorallocate($canvas, 30, 41, 65);
            imagefilledrectangle($canvas, 635, 72, 1136, 558, $panel);
            $initial = Str::upper(Str::substr(trim($product->name), 0, 1)) ?: 'S';
            $this->drawText($canvas, $initial, 180, 800, 395, $accent, true);
        }

        if ($logo) {
            imagedestroy($logo);
        }

        ob_start();
        imagejpeg($canvas, null, 88);
        $contents = (string) ob_get_clean();
        imagedestroy($canvas);

        return $contents;
    }

    private function paintBackground(\GdImage $image): void
    {
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $color = imagecolorallocate($image, 15 + (int) (15 * $ratio), 23 + (int) (12 * $ratio), 42 + (int) (20 * $ratio));
            imageline($image, 0, $y, self::WIDTH, $y, $color);
        }
    }

    private function primaryScreenshot(Product $product): ?string
    {
        $media = $product->media()
            ->orderByRaw("CASE WHEN type = 'screenshot' THEN 0 WHEN type = 'og' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->first();

        return $media ? $this->localMediaPath($media) : null;
    }

    private function localMediaPath(ProductMedia $media): ?string
    {
        return $this->localProductPath($media->path_medium)
            ?: $this->localProductPath($media->path);
    }

    private function localProductPath(?string $path): ?string
    {
        if (! is_string($path) || trim($path) === '' || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        $normalized = ltrim(preg_replace('#^storage/#', '', $path), '/');

        return Storage::disk('public')->exists($normalized)
            ? Storage::disk('public')->path($normalized)
            : null;
    }

    private function sourceImage(?string $path): ?\GdImage
    {
        if (! $path || ! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        try {
            return imagecreatefromstring($contents) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function drawCover(\GdImage $canvas, \GdImage $source, int $x, int $y, int $width, int $height, int $radius): void
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);
        $sourceX = (int) max(0, ($sourceWidth - $cropWidth) / 2);
        $sourceY = (int) max(0, ($sourceHeight - $cropHeight) / 2);

        $layer = imagecreatetruecolor($width, $height);
        imagecopyresampled($layer, $source, 0, 0, $sourceX, $sourceY, $width, $height, $cropWidth, $cropHeight);
        $this->copyRounded($canvas, $layer, $x, $y, $radius);
        imagedestroy($layer);
    }

    private function drawContained(\GdImage $canvas, \GdImage $source, int $x, int $y, int $width, int $height): void
    {
        $scale = min($width / imagesx($source), $height / imagesy($source));
        $targetWidth = max(1, (int) round(imagesx($source) * $scale));
        $targetHeight = max(1, (int) round(imagesy($source) * $scale));
        $targetX = $x + (int) (($width - $targetWidth) / 2);
        $targetY = $y + (int) (($height - $targetHeight) / 2);
        imagecopyresampled($canvas, $source, $targetX, $targetY, 0, 0, $targetWidth, $targetHeight, imagesx($source), imagesy($source));
    }

    private function copyRounded(\GdImage $canvas, \GdImage $layer, int $x, int $y, int $radius): void
    {
        $width = imagesx($layer);
        $height = imagesy($layer);
        for ($row = 0; $row < $height; $row++) {
            $inset = 0;
            if ($row < $radius) {
                $inset = $radius - (int) sqrt(max(0, $radius ** 2 - ($radius - $row) ** 2));
            } elseif ($row >= $height - $radius) {
                $distance = $row - ($height - $radius - 1);
                $inset = $radius - (int) sqrt(max(0, $radius ** 2 - $distance ** 2));
            }
            imagecopy($canvas, $layer, $x + $inset, $y + $row, $inset, $row, $width - (2 * $inset), 1);
        }
    }

    private function drawWrappedText(\GdImage $image, string $text, int $size, int $x, int $y, int $maxWidth, int $lineHeight, int $color, bool $bold, int $maxLines): void
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = trim($line.' '.$word);
            if ($line !== '' && $this->textWidth($candidate, $size, $bold) > $maxWidth) {
                $lines[] = $line;
                $line = $word;
                if (count($lines) === $maxLines - 1) {
                    break;
                }
            } else {
                $line = $candidate;
            }
        }
        if ($line !== '' && count($lines) < $maxLines) {
            $lines[] = $line;
        }
        foreach ($lines as $index => $value) {
            $this->drawText($image, $value, $size, $x, $y + ($index * $lineHeight), $color, $bold);
        }
    }

    private function drawText(\GdImage $image, string $text, int $size, int $x, int $y, int $color, bool $bold): void
    {
        $font = $this->font($bold);
        if ($font && function_exists('imagettftext')) {
            imagettftext($image, $size, 0, $x, $y, $color, $font, $text);

            return;
        }

        imagestring($image, 5, $x, max(0, $y - 16), $text, $color);
    }

    private function textWidth(string $text, int $size, bool $bold): int
    {
        $font = $this->font($bold);
        if ($font && function_exists('imagettfbbox')) {
            $box = imagettfbbox($size, 0, $font, $text);

            return abs($box[2] - $box[0]);
        }

        return strlen($text) * imagefontwidth(5);
    }

    private function font(bool $bold): ?string
    {
        $configured = config($bold ? 'product-og.font_bold' : 'product-og.font_regular');
        $candidates = array_filter([
            $configured,
            $bold ? '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf' : '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            $bold ? '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf' : '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            $bold ? '/System/Library/Fonts/Supplemental/Arial Bold.ttf' : '/System/Library/Fonts/Supplemental/Arial.ttf',
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
