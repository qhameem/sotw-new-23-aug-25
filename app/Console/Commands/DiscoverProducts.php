<?php

namespace App\Console\Commands;

use App\Models\ProductDiscoverySource;
use App\Services\ProductDiscoveryService;
use Illuminate\Console\Command;

class DiscoverProducts extends Command
{
    protected $signature = 'products:discover {--source= : Scan one source ID}';

    protected $description = 'Scan configured sources for product recommendations';

    public function handle(ProductDiscoveryService $discovery): int
    {
        $sources = ProductDiscoverySource::query()->where('is_active', true)
            ->when($this->option('source'), fn ($query, $id) => $query->whereKey($id))->get();

        $found = 0;
        $failures = 0;
        foreach ($sources as $source) {
            try {
                $count = $discovery->scan($source);
                $found += $count;
                $this->info("{$source->name}: {$count} items processed");
            } catch (\Throwable $exception) {
                $failures++;
                $this->error("{$source->name}: {$exception->getMessage()}");
            }
        }

        $this->info("Completed: {$found} items processed; {$failures} sources failed.");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
