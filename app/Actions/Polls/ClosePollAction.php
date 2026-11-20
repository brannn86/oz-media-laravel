<?php

namespace App\Actions\Polls;

use App\Actions\Concerns\BroadcastsSafely;
use App\Enums\PollStatus;
use App\Events\Live\PollUpdated;
use App\Models\Poll;

/**
 * Ends voting and broadcasts the final results (revealing the trivia answer).
 */
class ClosePollAction
{
    use BroadcastsSafely;

    public function handle(Poll $poll): Poll
    {
        if (! $poll->isLive()) {
            return $poll;
        }

        $poll->forceFill(['status' => PollStatus::Closed, 'closed_at' => now()])->save();
        $poll->load('options', 'station');

        $this->broadcastSafely(new PollUpdated($poll));

        return $poll;
    }
}
