<?php

namespace App\Actions\Auth;

use App\Models\User;

final class IssuedToken
{
    public function __construct(
        public readonly User $user,
        public readonly string $plainTextToken,
    ) {}
}
