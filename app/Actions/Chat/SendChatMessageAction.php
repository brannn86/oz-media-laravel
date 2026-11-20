<?php

namespace App\Actions\Chat;

use App\Actions\Chat\Concerns\BroadcastsChatEvents;
use App\Events\Chat\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Station;
use App\Models\User;
use App\Support\ProfanityFilter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Posts a listener's message to a station's live chat.
 *
 * Order of checks: valid text, not muted, cooldown (max of the global floor
 * and the station's slow mode, per listener per station), then the message
 * is profanity-masked, saved and broadcast.
 */
class SendChatMessageAction
{
    use BroadcastsChatEvents;

    public function __construct(private readonly ProfanityFilter $filter) {}

    /**
     * @throws ValidationException when the body is empty or too long
     * @throws AccessDeniedHttpException when the listener is muted
     * @throws ThrottleRequestsException when the listener is still cooling down (Retry-After header set)
     */
    public function handle(User $user, Station $station, string $body): ChatMessage
    {
        $body = trim((string) preg_replace('/\s+/u', ' ', $body));

        Validator::make(['body' => $body], [
            'body' => ['required', 'string', 'max:'.config('oz.chat.max_length')],
        ])->validate();

        if ($user->isChatMuted()) {
            throw new AccessDeniedHttpException(
                'You are muted in chat until '.$user->chat_muted_until?->setTimezone('Asia/Jakarta')->format('H:i').' WIB.'
            );
        }

        $this->enforceCooldown($user, $station);

        $message = $station->chatMessages()->create([
            'user_id' => $user->id,
            'body' => $this->filter->clean($body),
        ]);

        $message->setRelation('user', $user)->setRelation('station', $station);

        $this->broadcastSafely(new ChatMessageSent($message));

        return $message;
    }

    public function cooldownSeconds(User $user, Station $station): int
    {
        $floor = (int) config('oz.chat.min_interval_seconds');

        // Slow mode is for listeners; staff only get the anti-flood floor.
        return $user->isStaff() ? $floor : max($floor, $station->chat_slow_mode_seconds);
    }

    private function enforceCooldown(User $user, Station $station): void
    {
        $key = "chat:{$station->id}:{$user->id}";
        $seconds = $this->cooldownSeconds($user, $station);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $wait = RateLimiter::availableIn($key);

            throw new ThrottleRequestsException(
                "Slow down a little. You can send another message in {$wait}s.",
                null,
                ['Retry-After' => $wait],
            );
        }

        RateLimiter::hit($key, $seconds);
    }
}
