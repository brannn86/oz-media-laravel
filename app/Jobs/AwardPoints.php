<?php

namespace App\Jobs;

use App\Actions\Points\AwardPointsAction;
use App\Enums\PointReason;
use App\Models\Station;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Writes an OZ Poin award to the ledger off the request path (the `points`
 * queue), so heartbeats, votes and approvals stay fast under load.
 *
 * Safe to retry: the ledger row and balance change share one transaction,
 * so a failed attempt leaves nothing behind.
 */
class AwardPoints implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 30, 120, 600];

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public User $user,
        public int $points,
        public PointReason $reason,
        public ?Station $station = null,
        public array $meta = [],
    ) {
        $this->onQueue(config('oz.queues.points'));
    }

    public function handle(AwardPointsAction $award): void
    {
        $award->handle($this->user, $this->points, $this->reason, $this->station, $this->meta);
    }
}
