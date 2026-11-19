<?php

namespace App\Filament\Resources\Stations\Pages;

use App\Filament\Pages\StationSettings;
use App\Filament\Resources\Stations\StationResource;
use App\Models\Station;
use Filament\Resources\Pages\CreateRecord;

class CreateStation extends CreateRecord
{
    protected static string $resource = StationResource::class;

    protected function getRedirectUrl(): string
    {
        /** @var Station $station */
        $station = $this->getRecord();

        return StationSettings::canAccess()
            ? StationSettings::getUrl(['station' => $station->slug])
            : static::getResource()::getUrl('index');
    }
}
