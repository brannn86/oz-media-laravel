<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Production needs `php artisan schedule:run` every minute from cron (it
| stays up for the minute to run sub-minute tasks), or `schedule:work`.
|
*/

// Song on air from each station's Icecast stream.
Schedule::command('stations:sync-now-playing')
    ->everyFifteenSeconds()
    ->withoutOverlapping(1)
    ->onOneServer();

// Peak live listeners per station per day, for the admin dashboard.
Schedule::command('oz:sample-listeners')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Website listeners and viewers per minute (from the player pings), for Analytics.
Schedule::command('analytics:flush-audience')
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
