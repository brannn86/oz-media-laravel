<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Adds one read of an article to the day's totals (Analytics > News).
 */
class RecordPostRead implements ShouldQueue
{
    use Queueable;

    /** Scrolled this far (percent of the article text) counts as finished. */
    public const FINISHED_AT = 90;

    public function __construct(public int $postId, public string $date, public int $depth, public int $seconds)
    {
        $this->onQueue(config('oz.queues.analytics'));
    }

    public function handle(): void
    {
        $row = [
            'post_id' => $this->postId,
            'date' => $this->date,
            'reads' => 1,
            'seconds' => $this->seconds,
            'reached_25' => (int) ($this->depth >= 25),
            'reached_50' => (int) ($this->depth >= 50),
            'reached_75' => (int) ($this->depth >= 75),
            'finished' => (int) ($this->depth >= self::FINISHED_AT),
        ];

        // "excluded" is the row being inserted (PostgreSQL / SQLite upsert).
        DB::table('post_reads')->upsert([$row], ['post_id', 'date'], [
            'reads' => DB::raw('post_reads.reads + 1'),
            'seconds' => DB::raw('post_reads.seconds + excluded.seconds'),
            'reached_25' => DB::raw('post_reads.reached_25 + excluded.reached_25'),
            'reached_50' => DB::raw('post_reads.reached_50 + excluded.reached_50'),
            'reached_75' => DB::raw('post_reads.reached_75 + excluded.reached_75'),
            'finished' => DB::raw('post_reads.finished + excluded.finished'),
        ]);
    }
}
