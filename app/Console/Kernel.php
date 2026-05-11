<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\BackfillProductEditorialContent;
use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\PublishScheduledProducts;
use App\Console\Commands\PruneMagicLoginLinks;
use App\Support\ProductPublishSchedule;

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
        PublishScheduledProducts::class,
        PruneMagicLoginLinks::class,
        \App\Console\Commands\AddNofollowToProductDescriptions::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('sitemap:generate')->daily();
        $schedule->command('products:publish-scheduled')->dailyAt(ProductPublishSchedule::getPublishTime());
        $schedule->command('reminders:send-deadline')->everyMinute();
        $schedule->command('badge:verify')->weekly()->mondays()->at('09:00');
        $schedule->command('auth:prune-magic-links')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
