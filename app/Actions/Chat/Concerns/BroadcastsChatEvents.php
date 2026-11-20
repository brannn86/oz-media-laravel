<?php

namespace App\Actions\Chat\Concerns;

use App\Events\Chat\ChatEvent;
use Throwable;

trait BroadcastsChatEvents
{
    /**
     * The database is the source of truth: if Reverb is down the change still
     * stands and clients catch up from history on their next load.
     */
    protected function broadcastSafely(ChatEvent $event): void
    {
        try {
            event($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
