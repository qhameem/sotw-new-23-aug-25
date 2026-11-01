<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class FaviconIcoGenerator
{
    public function generate(string $sourcePath): string
    {
        $manager = new ImageManager(new Driver());
        $sizes = [16, 32, 48];
        $directory = '';
        $images = '';
        $offset = 6 + count($sizes) * 16;

        foreach ($sizes as $size) {
            $png = (string) $manager->read($sourcePath)->resize($size, $size)->toPng();
            $length = strlen($png);
            $directory .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, $length, $offset);
            $images .= $png;
            $offset += $length;
        }

        return pack('vvv', 0, 1, count($sizes)).$directory.$images;
    }
}
