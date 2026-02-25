<?php

namespace App\Console\Commands;

use App\Jobs\VerifyBadgePlacement;
use App\Models\Product;
use Illuminate\Console\Command;

class VerifyBadgePlacements extends Command
{
    protected $signature = 'badge:verify';
    protected $description = 'Verify badge placements for all badge-submitted products';

    public function handle(): int
    {
        $products = Product::where('submission_type', 'badge')
            ->where('is_published', true)
            ->get();

        $this->info("Found {$products->count()} badge-submitted products to verify.");

        foreach ($products as $product) {
            VerifyBadgePlacement::dispatch($product);
            $this->line("  Dispatched verification for: {$product->name} ({$product->slug})");
        }

        $this->info('All verification jobs dispatched.');
        return Command::SUCCESS;
    }
}
