<?php

use App\Services\AnalyticsService;
use App\Services\DomainRatingService;
use App\Support\ProductPublishSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ai-crawlers:import')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Laravel 12 loads scheduled tasks from this file. Check due products every
// minute so a delayed cron invocation still publishes every overdue product.
Schedule::command('products:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('sitemap:generate')->daily();

Schedule::command('sitemap:generate-alternatives')
    ->dailyAt(ProductPublishSchedule::getPublishTime())
    ->withoutOverlapping();

Schedule::command('reminders:send-deadline')->everyMinute();
Schedule::command('badge:verify')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('auth:prune-magic-links')->daily();

Schedule::command('products:discover')
    ->dailyAt('04:30')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('agent:refresh')->dailyAt('05:00')->withoutOverlapping();

Artisan::command('pricing:refresh-visitors', function (AnalyticsService $analytics) {
    $visitors = $analytics->fetchMonthlyVisitors();

    if ($visitors === null) {
        $this->warn('Google Analytics visitor data is unavailable.');

        return;
    }

    Cache::put(AnalyticsService::MONTHLY_VISITORS_CACHE_KEY, $visitors, now()->addDays(7));
})->purpose('Refresh the cached 30-day visitor count for Pricing');

Schedule::command('pricing:refresh-visitors')->daily()->withoutOverlapping();

Artisan::command('seo:refresh-domain-rating', function (DomainRatingService $domainRating) {
    if (! $domainRating->refresh()) {
        $this->error('Unable to refresh the Ahrefs Domain Rating. Check AHREFS_API_KEY, APP_URL, and API availability.');

        return 1;
    }

    $this->info('Ahrefs Domain Rating refreshed.');

    return 0;
})->purpose('Refresh the cached Ahrefs Domain Rating for the site');

Schedule::command('seo:refresh-domain-rating')
    ->dailyAt('05:15')
    ->when(fn () => filled(config('services.ahrefs.api_key')))
    ->withoutOverlapping()
    ->onOneServer();
