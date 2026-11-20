<?php

namespace App\Policies;

use App\Models\User;

/**
 * Hand-written: chat has no Filament resource, so Shield does not generate this.
 */
class ChatMessagePolicy
{
    /**
     * Pin, delete, mute and slow mode (Studio Mode).
     */
    public function moderate(User $user): bool
    {
        return $user->can('Moderate:ChatMessage');
    }
}
