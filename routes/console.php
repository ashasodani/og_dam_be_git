<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:expire-date')
     ->everyMinute()
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Handle app expiration logic every hour');

Schedule::command('notification:expire-date-notification')
    ->dailyAt('00:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Handle app expiration logic daily at midnight UTC');


Schedule::command('clean:done')
    ->cron('0 2 */2 * *')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Clean done items every two days');