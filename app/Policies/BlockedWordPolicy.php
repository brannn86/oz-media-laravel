<?php

namespace App\Policies;

use App\Models\BlockedWord;
use App\Models\User;

/**
 * The blocked-words list is part of chat moderation, so it follows the
 * `Moderate:ChatMessage` permission. Hand-written: the resource is excluded
 * from Shield generation.
 */
class BlockedWordPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->moderates($user);
    }

    public function create(User $user): bool
    {
        return $this->moderates($user);
    }

    public function update(User $user, BlockedWord $word): bool
    {
        return $this->moderates($user);
    }

    public function delete(User $user, BlockedWord $word): bool
    {
        return $this->moderates($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->moderates($user);
    }

    private function moderates(User $user): bool
    {
        return $user->can('Moderate:ChatMessage');
    }
}
