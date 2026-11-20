<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RewardRedemption;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RewardRedemptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RewardRedemption');
    }

    public function view(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('View:RewardRedemption');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RewardRedemption');
    }

    public function update(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('Update:RewardRedemption');
    }

    public function delete(AuthUser $authUser, RewardRedemption $rewardRedemption): bool
    {
        return $authUser->can('Delete:RewardRedemption');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RewardRedemption');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RewardRedemption');
    }
}
