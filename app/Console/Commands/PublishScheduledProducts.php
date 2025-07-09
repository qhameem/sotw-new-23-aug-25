<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PublishScheduledProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish products that have reached their scheduled publishing date (UTC).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for scheduled products to publish...');

        $nowUtc = Carbon::now('UTC');
        $productsToPublish = Product::where('approved', true)
            ->where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $nowUtc)
            ->get();

        if ($productsToPublish->isEmpty()) {
            $this->info('No products are currently scheduled to be published.');
            return 0;
        }

        $count = 0;
        foreach ($productsToPublish as $product) {
            $product->is_published = true;
            $product->save();
            $count++;
            $this->line("Published product: {$product->name} (ID: {$product->id})");
        }

        $message = "Successfully published {$count} product(s).";
        $this->info($message);
        Log::info($message);

        return 0;
    }
}
