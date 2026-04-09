<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run subscription status processor every hour
Schedule::command('subscriptions:process-statuses')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Send trial reminder emails daily at 9:00 AM
Schedule::command('subscriptions:send-trial-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Send subscription expiry reminder emails daily at 9:00 AM
Schedule::command('subscription:send-expiry-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();

// Process frozen accounts daily at 1:00 AM (warning emails + data deletion)
Schedule::command('subscription:process-frozen')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();
