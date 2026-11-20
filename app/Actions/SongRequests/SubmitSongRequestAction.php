<?php

namespace App\Actions\SongRequests;

use App\Actions\Concerns\BroadcastsSafely;
use App\Enums\SongRequestStatus;
use App\Events\Live\SongRequestUpdated;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\User;
use App\Support\ProfanityFilter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Adds a listener's song request to a station's live queue.
 *
 * Limits: chat-muted listeners can't request; a listener may have only a
 * couple of open requests per station, and must wait a few minutes between
 * requests. Text goes through the same profanity filter as chat.
 */
class SubmitSongRequestAction
{
    use BroadcastsSafely;

    public function __construct(private readonly ProfanityFilter $filter) {}

    /**
     * @param  array<string, mixed>  $input  song_title, artist, shoutout_message
     *
     * @throws ValidationException
     * @throws AccessDeniedHttpException when muted
     * @throws ThrottleRequestsException when requesting again too soon
     */
    public function handle(User $user, Station $station, array $input): SongRequest
    {
        $data = Validator::make($input, [
            'song_title' => ['required', 'string', 'max:150'],
            'artist' => ['required', 'string', 'max:150'],
            'shoutout_message' => ['nullable', 'string', 'max:280'],
        ])->validate();

        if ($user->isChatMuted()) {
            throw new AccessDeniedHttpException('You are muted, so song requests are paused for now.');
        }

        $open = $station->songRequests()
            ->where('user_id', $user->id)
            ->whereIn('status', SongRequestStatus::openValues())
            ->count();
        $maxOpen = (int) config('oz.requests.max_open_per_listener');

        if ($open >= $maxOpen) {
            throw ValidationException::withMessages([
                'song_title' => "You already have {$maxOpen} requests in the queue. Wait for one to be played.",
            ]);
        }

        $key = "song-request:{$station->id}:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $wait = RateLimiter::availableIn($key);

            throw new ThrottleRequestsException(
                'You can request another song in '.ceil($wait / 60).' min.',
                null,
                ['Retry-After' => $wait],
            );
        }

        RateLimiter::hit($key, (int) config('oz.requests.cooldown_seconds'));

        $request = $station->songRequests()->create([
            'user_id' => $user->id,
            'song_title' => $this->filter->clean(trim($data['song_title'])),
            'artist' => $this->filter->clean(trim($data['artist'])),
            'shoutout_message' => filled($data['shoutout_message'] ?? null)
                ? $this->filter->clean(trim($data['shoutout_message']))
                : null,
        ]);

        $request->setRelation('user', $user)->setRelation('station', $station);

        $this->broadcastSafely(new SongRequestUpdated($request));

        return $request;
    }
}
