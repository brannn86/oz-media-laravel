<?php

namespace App\Actions\Chat;

use App\Models\ChatMessage;
use App\Models\Station;
use Illuminate\Database\Eloquent\Collection;

/**
 * Recent messages (oldest first) plus the pinned one, for clients joining the chat.
 */
class GetChatHistoryAction
{
    /**
     * @return array{messages: Collection<int, ChatMessage>, pinned: ChatMessage|null}
     */
    public function handle(Station $station, ?int $limit = null): array
    {
        $limit ??= (int) config('oz.chat.history_size');

        $messages = $station->chatMessages()
            ->with(['user.roles'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return [
            'messages' => $messages,
            'pinned' => $station->chatMessages()->pinned()->with('user.roles')->first(),
        ];
    }
}
