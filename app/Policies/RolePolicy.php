<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * The admin roles are fixed (see App\Enums\AdminRole): devs may review and
 * tune their permissions, but nobody can create or delete roles from the UI.
 *
 * Hand-written on purpose: RoleResource is excluded from Shield generation.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isDev();
    }

    public function view(User $user, Role $role): bool
    {
        return $user->isDev();
    }

    public function update(User $user, Role $role): bool
    {
        return $user->isDev();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Role $role): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
