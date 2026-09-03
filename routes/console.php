<?php

use App\Support\ProductPublishSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
