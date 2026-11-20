<?php

namespace App\Actions\Broadcast;

use App\Models\Station;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Pusher\Pusher;
use RuntimeException;
use Throwable;

/**
 * Live listener counting on the `presence-station.{slug}.listeners` channel.
 *
 * Players join the channel while they are playing. Guests can join too (no
 * account needed to listen), identified by an opaque per-browser/device key
 * so one person with three tabs counts once. No personal data is shared
 * with other members.
 */
class ListenerPresence
{
    private const COUNT_CACHE_SECONDS = 3;

    /**
     * Signs a presence subscription for the given socket.
     *
     * @return array<string, mixed> The Pusher-protocol auth payload for the client.
     */
    public function authorize(Station $station, string $channelName, string $socketId, ?User $user, string $guestKey): array
    {
        if ($channelName !== $this->presenceChannel($station)) {
            throw new RuntimeException('Not a listener presence channel.');
        }

        $memberId = $user ? "user:{$user->id}" : 'guest:'.hash('sha256', $guestKey);

        $json = $this->pusher()->authorizePresenceChannel($channelName, $socketId, $memberId, ['listener' => true]);

        return (array) json_decode($json, true);
    }

    /**
     * Unique listeners on air right now, or null when Reverb can't be reached.
     */
    public function count(Station $station): ?int
    {
        return Cache::remember(
            "listeners:count:{$station->id}",
            self::COUNT_CACHE_SECONDS,
            function () use ($station): ?int {
                try {
                    return count($this->pusher()->getPresenceUsers($this->presenceChannel($station))->users ?? []);
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            },
        );
    }

    public function presenceChannel(Station $station): string
    {
        return 'presence-'.$station->listenersChannel();
    }

    private function pusher(): Pusher
    {
        $broadcaster = Broadcast::driver();

        if (! $broadcaster instanceof PusherBroadcaster) {
            throw new RuntimeException('Listener presence needs the Reverb (or Pusher) broadcaster.');
        }

        return $broadcaster->getPusher();
    }
}
