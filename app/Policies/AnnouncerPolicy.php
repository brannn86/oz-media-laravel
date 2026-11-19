<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Announcer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AnnouncerPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Announcer');
    }

    public function view(AuthUser $authUser, Announcer $announcer): bool
    {
        return $authUser->can('View:Announcer');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Announcer');
    }

    public function update(AuthUser $authUser, Announcer $announcer): bool
    {
        return $authUser->can('Update:Announcer');
    }

    public function delete(AuthUser $authUser, Announcer $announcer): bool
    {
        return $authUser->can('Delete:Announcer');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Announcer');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Announcer');
    }
}
