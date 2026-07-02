<?php

namespace App\Console\Commands;

use App\Jobs\SubmitUrlNotifications;
use App\Services\UrlNotificationService;
use Illuminate\Console\Command;

class NotifyUrlIndexing extends Command
{
    protected $signature = 'indexing:notify
        {urls* : One or more absolute URLs}
        {--deleted : Submit as URL_DELETED instead of URL_UPDATED}
        {--sync : Submit immediately instead of queueing}';

    protected $description = 'Submit URL notifications to all enabled indexing providers.';

    public function handle(UrlNotificationService $notifications): int
    {
        $urls = collect((array) $this->argument('urls'))
            ->filter(fn ($url) => is_string($url) && trim($url) !== '')
            ->map(fn (string $url) => trim($url))
            ->unique()
            ->values()
            ->all();

        if ($urls === []) {
            $this->error('Provide at least one absolute URL.');

            return self::FAILURE;
        }

        if (! $notifications->isEnabled()) {
            $this->error('No indexing provider is enabled.');

            return self::FAILURE;
        }

        $updatedUrls = $this->option('deleted') ? [] : $urls;
        $deletedUrls = $this->option('deleted') ? $urls : [];

        if ($this->option('sync')) {
            $notifications->submit($updatedUrls, $deletedUrls);
            $this->info('Indexing notification submitted successfully.');

            return self::SUCCESS;
        }

        SubmitUrlNotifications::dispatch($updatedUrls, $deletedUrls);
        $this->info('Indexing notification queued successfully.');

        return self::SUCCESS;
    }
}
