<?php

namespace App\Console\Commands;

use App\Models\AuthMagicLink;
use Illuminate\Console\Command;

class PruneMagicLoginLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:prune-magic-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired and consumed magic login links';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = AuthMagicLink::query()
            ->where(function ($query) {
                $query->whereNotNull('consumed_at')
                    ->orWhere('expires_at', '<', now()->subDay());
            })
            ->delete();

        $this->info("Deleted {$deleted} magic login link records.");

        return self::SUCCESS;
    }
}
