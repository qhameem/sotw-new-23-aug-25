<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class FeatureIconNormalizer
{
    public function normalize(UploadedFile $file): string
    {
        $source = imagecreatefromstring($file->getContent());
        if (! $source) {
            throw ValidationException::withMessages(['icon' => 'Unable to read this image.']);
        }

        $scale = min(1, 512 / max(imagesx($source), imagesy($source)));
        $width = max(1, (int) round(imagesx($source) * $scale));
        $height = max(1, (int) round(imagesy($source) * $scale));
        $mask = imagecreatetruecolor($width, $height);
        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        imagefill($mask, 0, 0, imagecolorallocatealpha($mask, 0, 0, 0, 127));
        imagecopyresampled($mask, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        imagedestroy($source);

        $transparent = false;
        for ($y = 0; $y < $height && ! $transparent; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (((imagecolorat($mask, $x, $y) >> 24) & 127) > 0) {
                    $transparent = true;
                    break;
                }
            }
        }

        $left = $width;
        $top = $height;
        $right = $bottom = -1;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $pixel = imagecolorat($mask, $x, $y);
                $alpha = ($pixel >> 24) & 127;
                if (! $transparent) {
                    // Treat white backgrounds as transparent; retain colored artwork as a silhouette.
                    $alpha = (int) round(min(($pixel >> 16) & 255, ($pixel >> 8) & 255, $pixel & 255) / 255 * 127);
                }
                imagesetpixel($mask, $x, $y, imagecolorallocatealpha($mask, 0, 0, 0, $alpha));
                if ($alpha < 120) {
                    $left = min($left, $x);
                    $top = min($top, $y);
                    $right = max($right, $x);
                    $bottom = max($bottom, $y);
                }
            }
        }

        if ($right < $left) {
            imagedestroy($mask);
            throw ValidationException::withMessages(['icon' => 'Upload an icon with visible artwork.']);
        }

        $output = imagecreatetruecolor(64, 64);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;
        $ratio = 64 / max($cropWidth, $cropHeight);
        $targetWidth = max(1, (int) round($cropWidth * $ratio));
        $targetHeight = max(1, (int) round($cropHeight * $ratio));
        imagecopyresampled($output, $mask, (int) ((64 - $targetWidth) / 2), (int) ((64 - $targetHeight) / 2), $left, $top, $targetWidth, $targetHeight, $cropWidth, $cropHeight);
        ob_start();
        imagepng($output);
        $png = ob_get_clean();
        imagedestroy($mask);
        imagedestroy($output);

        return $png;
    }
}
