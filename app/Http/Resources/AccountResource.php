<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in listener's own account (GET /api/v1/me).
 *
 * @mixin User
 */
class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'is_staff' => $this->isStaff(),
            'has_password' => $this->password !== null,
            'has_google' => $this->google_id !== null,
            'chat_muted_until' => $this->isChatMuted() ? $this->chat_muted_until?->toIso8601String() : null,
        ];
    }
}
