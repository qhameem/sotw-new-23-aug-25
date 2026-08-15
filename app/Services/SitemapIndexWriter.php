<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SitemapIndexWriter
{
    public function write(string $indexPath, string $sitemapDirectory): void
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        $paths = File::glob($sitemapDirectory.DIRECTORY_SEPARATOR.'*.xml');
        sort($paths);

        foreach ($paths as $path) {
            $relativePath = Str::of($path)
                ->after(public_path().DIRECTORY_SEPARATOR)
                ->replace(DIRECTORY_SEPARATOR, '/')
                ->toString();
            $lastModified = CarbonImmutable::createFromTimestamp(File::lastModified($path))->toAtomString();

            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>'.htmlspecialchars(url($relativePath), ENT_XML1).'</loc>';
            $lines[] = '    <lastmod>'.htmlspecialchars($lastModified, ENT_XML1).'</lastmod>';
            $lines[] = '  </sitemap>';
        }

        $lines[] = '</sitemapindex>';

        File::put($indexPath, implode(PHP_EOL, $lines).PHP_EOL);
    }
}
