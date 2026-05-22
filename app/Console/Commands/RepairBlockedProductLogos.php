<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductLogoResolver;
use App\Support\ProductLogo;
use Illuminate\Console\Command;

class RepairBlockedProductLogos extends Command
{
    protected $signature = 'products:repair-blocked-logos {--dry-run : Show replacements without saving}';

    protected $description = 'Replace blocked Google favicon logo URLs with a usable logo or favicon URL.';

    public function handle(ProductLogoResolver $productLogoResolver): int
    {
        $products = Product::query()
            ->whereNotNull('logo')
            ->get()
            ->filter(fn (Product $product) => ProductLogo::isBlockedExternalFaviconUrl($product->logo))
            ->values();

        if ($products->isEmpty()) {
            $this->info('No blocked product logos found.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $replacementLogo = $productLogoResolver->discoverReplacementLogoUrl($product->link);

            if (!$replacementLogo) {
                $skipped++;
                $this->warn("Skipped {$product->id} {$product->name}: no replacement logo found.");
                continue;
            }

            if ($this->option('dry-run')) {
                $updated++;
                $this->line("Would update {$product->id} {$product->name} -> {$replacementLogo}");
                continue;
            }

            $product->forceFill([
                'logo' => $replacementLogo,
            ])->save();

            $updated++;
            $this->info("Updated {$product->id} {$product->name}");
        }

        $this->newLine();
        $this->line("Checked {$products->count()} blocked product logos.");
        $this->line("Resolved {$updated}.");
        $this->line("Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
