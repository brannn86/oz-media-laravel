<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Station;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class StationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Station');
    }

    public function view(AuthUser $authUser, Station $station): bool
    {
        return $authUser->can('View:Station');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Station');
    }

    public function update(AuthUser $authUser, Station $station): bool
    {
        return $authUser->can('Update:Station');
    }

    public function delete(AuthUser $authUser, Station $station): bool
    {
        return $authUser->can('Delete:Station');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Station');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Station');
    }

    public function toggleVideoLive(AuthUser $authUser, Station $station): bool
    {
        return $authUser->can('ToggleVideoLive:Station');
    }
}
