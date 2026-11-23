<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| OZ Media maintenance (run `php artisan schedule:work` locally, or the
| usual `* * * * * php artisan schedule:run` cron in production)
|--------------------------------------------------------------------------
*/

// Peak live listeners per station per day, for the admin dashboard.
Schedule::command('oz:sample-listeners')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Safety net for listening-heartbeat keys that lost their TTL.
Schedule::command('oz:prune-redis-keys')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Move old chat to the archive table and purge long-deleted messages (queued).
Schedule::command('oz:archive-chat')
    ->dailyAt('03:30')
    ->timezone('Asia/Jakarta')
    ->onOneServer();
