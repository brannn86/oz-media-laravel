<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Counts one article view for the day (feeds "Top OZ News" on the dashboard).
 */
class RecordPostView implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $postId, public string $date)
    {
        $this->onQueue(config('oz.queues.analytics'));
    }

    public function handle(): void
    {
        DB::table('post_views')->upsert(
            [['post_id' => $this->postId, 'date' => $this->date, 'views' => 1]],
            ['post_id', 'date'],
            ['views' => DB::raw('post_views.views + 1')],
        );
    }
}
