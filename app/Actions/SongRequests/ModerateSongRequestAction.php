<?php

namespace App\Actions\SongRequests;

use App\Actions\Concerns\BroadcastsSafely;
use App\Actions\Points\AwardPointsAction;
use App\Enums\PointReason;
use App\Enums\SongRequestStatus;
use App\Events\Live\SongRequestUpdated;
use App\Models\SongRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Studio decisions on the request queue.
 *
 * Waiting -> Coming up (approved) -> Played, or Waiting -> Rejected.
 * A waiting request can also go straight to Played. Approving pays the
 * requester OZ Poin exactly once, even if two people click at the same time.
 */
class ModerateSongRequestAction
{
    use BroadcastsSafely;

    public function __construct(private readonly AwardPointsAction $award) {}

    public function approve(SongRequest $request): SongRequest
    {
        DB::transaction(function () use ($request): void {
            $this->transition($request, [SongRequestStatus::Pending], SongRequestStatus::Approved);

            $points = (int) config('oz.points.song_request_approved');

            if ($points > 0) {
                $this->award->handle($request->user, $points, PointReason::SongRequestApproved, $request->station, [
                    'song_request_id' => $request->id,
                ]);
            }
        });

        return $this->announce($request);
    }

    public function reject(SongRequest $request): SongRequest
    {
        $this->transition($request, [SongRequestStatus::Pending, SongRequestStatus::Approved], SongRequestStatus::Rejected);

        return $this->announce($request);
    }

    public function markPlayed(SongRequest $request): SongRequest
    {
        $this->transition($request, [SongRequestStatus::Pending, SongRequestStatus::Approved], SongRequestStatus::Played);

        return $this->announce($request);
    }

    /**
     * Atomic status change: only succeeds if the row is still in an allowed
     * state, so a double click (or two moderators) can't apply it twice.
     *
     * @param  list<SongRequestStatus>  $from
     */
    private function transition(SongRequest $request, array $from, SongRequestStatus $to): void
    {
        $changed = SongRequest::query()
            ->whereKey($request->getKey())
            ->whereIn('status', array_map(fn (SongRequestStatus $status): string => $status->value, $from))
            ->update(['status' => $to->value, 'decided_at' => now(), 'updated_at' => now()]);

        $request->refresh();

        if ($changed === 0) {
            throw ValidationException::withMessages([
                'request' => "This request is already {$request->status->getLabel()}.",
            ]);
        }
    }

    private function announce(SongRequest $request): SongRequest
    {
        $request->loadMissing('user', 'station');

        $this->broadcastSafely(new SongRequestUpdated($request));

        return $request;
    }
}
