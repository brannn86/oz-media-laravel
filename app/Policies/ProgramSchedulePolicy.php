<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProgramSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProgramSchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProgramSchedule');
    }

    public function view(AuthUser $authUser, ProgramSchedule $programSchedule): bool
    {
        return $authUser->can('View:ProgramSchedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProgramSchedule');
    }

    public function update(AuthUser $authUser, ProgramSchedule $programSchedule): bool
    {
        return $authUser->can('Update:ProgramSchedule');
    }

    public function delete(AuthUser $authUser, ProgramSchedule $programSchedule): bool
    {
        return $authUser->can('Delete:ProgramSchedule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProgramSchedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProgramSchedule');
    }
}
