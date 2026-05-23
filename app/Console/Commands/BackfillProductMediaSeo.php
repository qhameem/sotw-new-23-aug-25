<?php

namespace App\Console\Commands;

use App\Models\ProductMedia;
use App\Support\ProductMediaSeo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillProductMediaSeo extends Command
{
    protected $signature = 'product-media:backfill-seo {product? : Optional product slug} {--dry-run : Preview changes without writing}';

    protected $description = 'Backfill SEO-friendly filenames and alt text for existing product media.';

    public function handle(): int
    {
        $query = ProductMedia::query()
            ->with('product')
            ->whereNotNull('product_id')
            ->orderBy('product_id')
            ->orderBy('id');

        if ($slug = $this->argument('product')) {
            $query->whereHas('product', fn ($productQuery) => $productQuery->where('slug', $slug));
        }

        $mediaItems = $query->get();

        if ($mediaItems->isEmpty()) {
            $this->warn('No product media records found for backfill.');

            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');
        $processed = 0;
        $renamed = 0;
        $altUpdated = 0;
        $missingOriginals = 0;

        foreach ($mediaItems->groupBy('product_id') as $productMediaItems) {
            $position = 0;

            foreach ($productMediaItems as $media) {
                $product = $media->product;

                if (!$product) {
                    continue;
                }

                $position++;
                $processed++;

                $extension = strtolower(pathinfo((string) $media->path, PATHINFO_EXTENSION)) ?: $this->defaultExtensionForType((string) $media->type);
                $expectedFilename = ProductMediaSeo::productMediaFilename($product, (string) $media->type, $extension, $position);
                $expectedPath = 'product_media/' . $expectedFilename;
                $expectedAltText = ProductMediaSeo::productMediaAltText($product, (string) $media->type, $position);

                $newPath = $media->path;
                $newThumbPath = $media->path_thumb;
                $newMediumPath = $media->path_medium;

                if ($this->isManagedMediaPath((string) $media->path) && $media->path !== $expectedPath) {
                    if (!$disk->exists($media->path)) {
                        $missingOriginals++;
                        $this->warn("Missing original file for media ID {$media->id}: {$media->path}");
                    } else {
                        $newPath = $this->uniquePathForMedia($disk, $expectedPath, $media->id);

                        if (!$isDryRun) {
                            $disk->move($media->path, $newPath);
                        }

                        $newThumbPath = $this->renameDerivativePath($disk, $media->path_thumb, $newPath, $isDryRun);
                        $newMediumPath = $this->renameDerivativePath($disk, $media->path_medium, $newPath, $isDryRun, 'medium_');
                        $renamed++;
                    }
                }

                $altChanged = trim((string) $media->alt_text) !== $expectedAltText;
                if ($altChanged) {
                    $altUpdated++;
                }

                if (!$isDryRun && (($newPath !== $media->path) || ($newThumbPath !== $media->path_thumb) || ($newMediumPath !== $media->path_medium) || $altChanged)) {
                    $media->forceFill([
                        'path' => $newPath,
                        'path_thumb' => $newThumbPath,
                        'path_medium' => $newMediumPath,
                        'alt_text' => $expectedAltText,
                    ])->saveQuietly();
                }
            }
        }

        $modeLabel = $isDryRun ? 'Dry run complete.' : 'Backfill complete.';
        $this->info($modeLabel);
        $this->line("Processed: {$processed}");
        $this->line("Renamed: {$renamed}");
        $this->line("Alt text updated: {$altUpdated}");
        $this->line("Missing originals: {$missingOriginals}");

        return self::SUCCESS;
    }

    protected function renameDerivativePath($disk, ?string $derivativePath, string $newPath, bool $isDryRun, string $prefix = 'thumb_'): ?string
    {
        if (!is_string($derivativePath) || trim($derivativePath) === '' || !$disk->exists($derivativePath)) {
            return null;
        }

        $directory = dirname($newPath);
        $filename = basename($newPath);
        $newDerivativePath = trim($directory . '/' . $prefix . $filename, '/');

        if ($derivativePath === $newDerivativePath) {
            return $derivativePath;
        }

        $newDerivativePath = $this->uniquePathForMedia($disk, $newDerivativePath, null);
        if (!$isDryRun) {
            $disk->move($derivativePath, $newDerivativePath);
        }

        return $newDerivativePath;
    }

    protected function uniquePathForMedia($disk, string $targetPath, ?int $mediaId): string
    {
        if (!$disk->exists($targetPath)) {
            return $targetPath;
        }

        $directory = dirname($targetPath);
        $filename = pathinfo($targetPath, PATHINFO_FILENAME);
        $extension = pathinfo($targetPath, PATHINFO_EXTENSION);
        $suffix = $mediaId ? '-m' . $mediaId : '-copy';
        $candidate = trim($directory . '/' . $filename . $suffix . ($extension !== '' ? '.' . $extension : ''), '/');

        if (!$disk->exists($candidate)) {
            return $candidate;
        }

        $counter = 2;
        while ($disk->exists($candidate)) {
            $candidate = trim($directory . '/' . $filename . $suffix . '-' . $counter . ($extension !== '' ? '.' . $extension : ''), '/');
            $counter++;
        }

        return $candidate;
    }

    protected function isManagedMediaPath(string $path): bool
    {
        return str_starts_with($path, 'product_media/');
    }

    protected function defaultExtensionForType(string $type): string
    {
        return $type === 'video' ? 'mp4' : 'webp';
    }
}
