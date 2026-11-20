<?php

namespace App\Filament\Resources\RewardRedemptions\Pages;

use App\Filament\Resources\RewardRedemptions\RewardRedemptionResource;
use Filament\Resources\Pages\ManageRecords;

/**
 * No create button: redemptions only come from listeners.
 */
class ManageRewardRedemptions extends ManageRecords
{
    protected static string $resource = RewardRedemptionResource::class;
}
