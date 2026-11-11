<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class PublicImageDimensions
{
    /** @return array{int, int}|null */
    public static function forPath(?string $path): ?array
    {
        if (! $path || str_contains($path, '://') || config('filesystems.disks.public.driver') !== 'local') {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        if (in_array('..', explode('/', $path), true)) {
            return null;
        }

        $disk = Storage::disk('public');
        $file = $disk->path(ltrim($path, '/'));
        $dimensions = is_file($file) ? @getimagesize($file) : false;

        return $dimensions && $dimensions[0] > 0 && $dimensions[1] > 0
            ? [$dimensions[0], $dimensions[1]]
            : null;
    }
}
