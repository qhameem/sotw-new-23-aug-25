<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductLogoStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeProductLogos extends Command
{
    protected $signature = 'products:optimize-logos {--dry-run : Preview changes without saving them}';

    protected $description = 'Optimize existing product logos by resizing and compressing them into local storage.';

    public function handle(ProductLogoStorageService $productLogoStorageService): int
    {
        $products = Product::query()
            ->whereNotNull('logo')
            ->where('logo', '!=', '')
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            $this->info('No product logos found.');

            return self::SUCCESS;
        }

        $processed = 0;
        $optimized = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($products as $product) {
            $processed++;
            $currentLogo = (string) $product->logo;

            try {
                $optimizedPath = null;

                if (filter_var($currentLogo, FILTER_VALIDATE_URL)) {
                    $optimizedPath = $productLogoStorageService->storeRemoteUrl($currentLogo);
                } else {
                    $optimizedPath = $productLogoStorageService->storePublicDiskPath($currentLogo);
                }

                if (!$optimizedPath) {
                    $skipped++;
                    $this->warn("Skipped {$product->id} {$product->name}: logo could not be optimized.");
                    continue;
                }

                if ($this->option('dry-run')) {
                    $optimized++;
                    $this->line("Would optimize {$product->id} {$product->name}: {$currentLogo} -> {$optimizedPath}");

                    if (!filter_var($currentLogo, FILTER_VALIDATE_URL) && Storage::disk('public')->exists($optimizedPath)) {
                        Storage::disk('public')->delete($optimizedPath);
                    }

                    continue;
                }

                $product->forceFill([
                    'logo' => $optimizedPath,
                ])->save();

                if (
                    !filter_var($currentLogo, FILTER_VALIDATE_URL)
                    && $currentLogo !== $optimizedPath
                    && Storage::disk('public')->exists($currentLogo)
                ) {
                    Storage::disk('public')->delete($currentLogo);
                }

                $optimized++;
                $this->info("Optimized {$product->id} {$product->name}");
            } catch (\Throwable $throwable) {
                $failed++;
                $this->error("Failed {$product->id} {$product->name}: {$throwable->getMessage()}");
            }
        }

        $this->newLine();
        $this->line("Processed: {$processed}");
        $this->line("Optimized: {$optimized}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failed: {$failed}");

        if ($this->option('dry-run')) {
            $this->line('Dry run complete.');
        }

        return self::SUCCESS;
    }
}
