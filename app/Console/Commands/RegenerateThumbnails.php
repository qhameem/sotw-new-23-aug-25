<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class RegenerateThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnails:regenerate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate thumbnails for all product media images';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting thumbnail regeneration...');

        $mediaItems = ProductMedia::where('type', 'image')->get();
        $manager = new ImageManager(new Driver());

        $bar = $this->output->createProgressBar($mediaItems->count());
        $bar->start();

        foreach ($mediaItems as $media) {
            try {
                if (!Storage::disk('public')->exists($media->path)) {
                    $this->error("File not found: {$media->path}");
                    continue;
                }

                $filename = basename($media->path);
                $directory = dirname($media->path);
                $fullPath = Storage::disk('public')->path($media->path);

                try {
                    // Generate Thumbnail (300px width)
                    $imageThumb = $manager->read($fullPath);
                } catch (\Throwable $e) {
                    $this->error("Skipping ID {$media->id} ({$filename}): Unable to read image (possibly unsupported format). Error: " . $e->getMessage());
                    continue;
                }

                $imageThumb->scale(width: 300);
                $thumbFilename = 'thumb_' . $filename;
                $pathThumb = $directory . '/' . $thumbFilename;
                Storage::disk('public')->put($pathThumb, (string) $imageThumb->encode());

                // Generate Medium (800px width)
                $imageMedium = $manager->read($fullPath); // Re-read to ensure clean state
                $imageMedium->scale(width: 800);
                $mediumFilename = 'medium_' . $filename;
                $pathMedium = $directory . '/' . $mediumFilename;
                Storage::disk('public')->put($pathMedium, (string) $imageMedium->encode());

                $media->update([
                    'path_thumb' => $pathThumb,
                    'path_medium' => $pathMedium,
                ]);

            } catch (\Throwable $e) {
                $this->error("Failed to process ID {$media->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Thumbnail regeneration completed.');
    }
}
