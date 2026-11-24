<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Counts one live video reaction for the day (feeds Analytics > Video).
 */
class RecordReaction implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $stationId, public string $type, public string $date)
    {
        $this->onQueue(config('oz.queues.analytics'));
    }

    public function handle(): void
    {
        DB::table('reaction_counts')->upsert(
            [['station_id' => $this->stationId, 'date' => $this->date, 'type' => $this->type, 'count' => 1]],
            ['station_id', 'date', 'type'],
            ['count' => DB::raw('reaction_counts.count + 1')],
        );
    }
}
