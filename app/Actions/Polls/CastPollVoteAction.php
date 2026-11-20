<?php

namespace App\Actions\Polls;

use App\Actions\Concerns\BroadcastsSafely;
use App\Actions\Points\AwardPointsAction;
use App\Enums\PointReason;
use App\Events\Live\PollUpdated;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One vote per listener per poll (a unique index backs this up), counted
 * on the option and paid in OZ Poin. Results are broadcast to everyone.
 */
class CastPollVoteAction
{
    use BroadcastsSafely;

    public function __construct(private readonly AwardPointsAction $award) {}

    /**
     * @throws ValidationException when the poll is closed, the option is wrong, or you already voted
     */
    public function handle(User $user, Poll $poll, int $optionId): Poll
    {
        if (! $poll->isLive()) {
            throw ValidationException::withMessages(['option_id' => 'Voting has closed for this poll.']);
        }

        $option = $poll->options()->whereKey($optionId)->first();

        if (! $option instanceof PollOption) {
            throw ValidationException::withMessages(['option_id' => 'That answer is not part of this poll.']);
        }

        try {
            DB::transaction(function () use ($user, $poll, $option): void {
                PollVote::create([
                    'poll_id' => $poll->id,
                    'poll_option_id' => $option->id,
                    'user_id' => $user->id,
                ]);

                $option->increment('votes_count');

                $points = (int) config('oz.points.poll_vote');

                if ($points > 0) {
                    $this->award->handle($user, $points, PointReason::PollVote, $poll->station, ['poll_id' => $poll->id]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['option_id' => 'You already voted in this poll.']);
        }

        $poll->load('options', 'station');

        $this->broadcastSafely(new PollUpdated($poll));

        return $poll;
    }

    public function votedOptionId(User $user, Poll $poll): ?int
    {
        $id = $poll->votes()->where('user_id', $user->id)->value('poll_option_id');

        return $id === null ? null : (int) $id;
    }
}
