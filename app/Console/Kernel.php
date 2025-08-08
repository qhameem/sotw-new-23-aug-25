<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateSitemap;
use App\Console\Commands\PublishScheduledProducts;
use Illuminate\Support\Facades\Storage;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GenerateSitemap::class,
        PublishScheduledProducts::class,
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
        $settings = Storage::disk('local')->exists('settings.json') ? json_decode(Storage::disk('local')->get('settings.json'), true) : [];
        $publishTime = $settings['product_publish_time'] ?? '07:00';
        $schedule->command('products:publish-scheduled')->dailyAt($publishTime);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}