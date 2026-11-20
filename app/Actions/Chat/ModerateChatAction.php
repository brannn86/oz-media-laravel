<?php

namespace App\Actions\Chat;

use App\Actions\Concerns\BroadcastsSafely;
use App\Events\Chat\ChatMessageDeleted;
use App\Events\Chat\ChatMessagePinned;
use App\Events\Chat\ChatSettingsUpdated;
use App\Models\ChatMessage;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Studio Mode moderation: pin, delete, mute and slow mode.
 *
 * Authorization happens at the boundary (ChatMessagePolicy::moderate) so
 * the web panel and a future mobile moderator app share these rules.
 */
class ModerateChatAction
{
    use BroadcastsSafely;

    /**
     * Pins a message, unpinning whatever was pinned before (one per station).
     */
    public function pin(ChatMessage $message): ChatMessage
    {
        DB::transaction(function () use ($message): void {
            ChatMessage::query()
                ->where('station_id', $message->station_id)
                ->whereKeyNot($message->getKey())
                ->pinned()
                ->update(['is_pinned' => false]);

            $message->update(['is_pinned' => true]);
        });

        $this->broadcastSafely(new ChatMessagePinned($message->load('user', 'station')));

        return $message;
    }

    public function unpin(ChatMessage $message): ChatMessage
    {
        $message->update(['is_pinned' => false]);

        $this->broadcastSafely(new ChatMessagePinned($message->load('user', 'station')));

        return $message;
    }

    public function delete(ChatMessage $message): void
    {
        $message->update(['is_pinned' => false]);
        $message->delete();

        $this->broadcastSafely(new ChatMessageDeleted($message->station, $message->id));
    }

    /**
     * Mutes a listener across all station chats. Staff cannot be muted.
     */
    public function mute(User $user, int $minutes): User
    {
        if ($user->isStaff()) {
            throw new InvalidArgumentException('Staff accounts cannot be muted.');
        }

        $user->forceFill(['chat_muted_until' => now()->addMinutes($minutes)])->save();

        return $user;
    }

    public function unmute(User $user): User
    {
        $user->forceFill(['chat_muted_until' => null])->save();

        return $user;
    }

    public function setSlowMode(Station $station, int $seconds): Station
    {
        if ($seconds < 0 || $seconds > 3600) {
            throw new InvalidArgumentException('Slow mode must be between 0 and 3600 seconds.');
        }

        $station->update(['chat_slow_mode_seconds' => $seconds]);

        $this->broadcastSafely(new ChatSettingsUpdated($station));

        return $station;
    }
}
