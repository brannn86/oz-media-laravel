<?php

namespace App\Console\Commands;

use App\Actions\Broadcast\ListenerPresence;
use App\Models\Station;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Records each station's peak live listener count per WIB day (guests
 * included), for the admin dashboard. Runs every minute.
 */
class SampleListeners extends Command
{
    protected $signature = 'oz:sample-listeners';

    protected $description = 'Sample live listener counts into station_daily_stats';

    public function handle(ListenerPresence $presence): int
    {
        $date = now('Asia/Jakarta')->toDateString();

        foreach (Station::query()->active()->get() as $station) {
            $count = $presence->count($station);

            if ($count === null) {
                continue;
            }

            DB::table('station_daily_stats')->upsert(
                [['station_id' => $station->id, 'date' => $date, 'peak_listeners' => $count]],
                ['station_id', 'date'],
                ['peak_listeners' => DB::raw('case when station_daily_stats.peak_listeners > excluded.peak_listeners then station_daily_stats.peak_listeners else excluded.peak_listeners end')],
            );
        }

        return self::SUCCESS;
    }
}
