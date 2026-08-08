<?php

namespace App\Console;

use App\Console\Commands\BackfillProductEditorialContent;
use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\NotifyUrlIndexing;
use App\Console\Commands\OptimizeProductLogos;
use App\Console\Commands\PruneMagicLoginLinks;
use App\Console\Commands\PublishScheduledProducts;
use App\Console\Commands\RepairBlockedProductLogos;
use App\Support\ProductPublishSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        BackfillProductEditorialContent::class,
        GenerateSitemap::class,
        NotifyUrlIndexing::class,
        OptimizeProductLogos::class,
        PublishScheduledProducts::class,
        PruneMagicLoginLinks::class,
        RepairBlockedProductLogos::class,
        \App\Console\Commands\AddNofollowToProductDescriptions::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('sitemap:generate')->daily();
        $schedule->command('products:publish-scheduled')->dailyAt(ProductPublishSchedule::getPublishTime());
        $schedule->command('reminders:send-deadline')->everyMinute();
        $schedule->command('badge:verify')->dailyAt('03:00');
        $schedule->command('auth:prune-magic-links')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
