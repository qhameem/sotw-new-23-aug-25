<?php

namespace App\Console\Commands;

use App\Models\StartupDirectory;
use App\Services\DomainRatingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefreshStartupDirectoryRatings extends Command
{
    protected $signature = 'directories:refresh-dr';

    protected $description = 'Refresh startup directory Domain Ratings from Ahrefs';

    public function handle(DomainRatingService $ahrefs): int
    {
        $checkedAt = now();
        $directories = StartupDirectory::query()->get(['id', 'website_url', 'dr'])
            ->toBase()
            ->groupBy(fn (StartupDirectory $directory) => strtolower($directory->domain()))
            ->except('');

        $ratings = [];
        try {
            foreach ($directories->keys()->chunk(DomainRatingService::BATCH_SIZE) as $batch) {
                $domains = $batch->values()->all();
                $returned = $ahrefs->fetchBatch($domains);
                $matched = array_intersect_key($returned, array_flip($domains));
                $this->line(sprintf('Requested %d URLs, received %d usable scores, matched %d domains.', count($domains), count($returned), count($matched)), null, 'v');
                $ratings += $matched;
            }
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $updated = DB::transaction(function () use ($ratings, $directories, $checkedAt) {
            $updated = 0;
            foreach ($ratings as $domain => $rating) {
                foreach ($directories[$domain] as $directory) {
                    $updated += StartupDirectory::query()->whereKey($directory->id)
                        ->where('website_url', $directory->website_url)
                        ->where('dr', $directory->dr)
                        ->where(fn ($query) => $query->whereNull('dr_updated_at')->orWhere('dr_updated_at', '<=', $checkedAt))
                        ->update(['dr' => (int) round($rating), 'dr_updated_at' => $checkedAt]);
                }
            }

            return $updated;
        });

        $missing = $directories->count() - count($ratings);
        if ($missing > 0) {
            $this->warn("Ahrefs returned no usable score for {$missing} domains. Existing scores were retained.");
        }
        $this->info("Refreshed DR for {$updated} directory listings.");

        return $missing > 0 && $ratings === [] ? self::FAILURE : self::SUCCESS;
    }
}
