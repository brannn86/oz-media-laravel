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
