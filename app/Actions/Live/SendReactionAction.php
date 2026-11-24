<?php

namespace App\Actions\Live;

use App\Enums\ReactionType;
use App\Events\Live\StudioReactionSent;
use App\Models\Station;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Floating fire / clap / heart over the video livestream.
 *
 * Only while the station is in Video Livestream mode, throttled per viewer,
 * and not echoed back to the sender's own socket (the client shows its own
 * reaction instantly).
 */
class SendReactionAction
{
    /**
     * @param  string  $viewerKey  Account id, or session/device id for guests.
     *
     * @throws ValidationException when the station has no video livestream
     * @throws ThrottleRequestsException when reacting too fast
     */
    public function handle(Station $station, ReactionType $type, string $viewerKey): void
    {
        if (! $station->hasVideoStream()) {
            throw ValidationException::withMessages(['type' => 'Reactions open when the station has a video livestream.']);
        }

        $key = "reaction:{$station->id}:".hash('sha256', $viewerKey);

        if (! RateLimiter::attempt($key, (int) config('oz.reactions.max'), fn () => true, (int) config('oz.reactions.per_seconds'))) {
            throw new ThrottleRequestsException('Easy there!', null, ['Retry-After' => RateLimiter::availableIn($key)]);
        }

        try {
            // Skip the sender's socket (X-Socket-ID header): their player already shows it.
            event((new StudioReactionSent($station, $type))->dontBroadcastToCurrentUser());
        } catch (Throwable $e) {
            report($e);
        }
    }
}
