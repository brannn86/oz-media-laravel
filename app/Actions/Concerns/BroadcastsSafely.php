<?php

namespace App\Actions\Concerns;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Throwable;

/**
 * Live updates are best-effort: the database is the source of truth, so a
 * Reverb outage must never undo or fail the change being broadcast. Clients
 * catch up from the API on their next load.
 */
trait BroadcastsSafely
{
    protected function broadcastSafely(ShouldBroadcast $event): void
    {
        try {
            event($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
