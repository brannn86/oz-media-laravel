<?php

namespace App\Filament\Resources\Announcers\Pages;

use App\Filament\Resources\Announcers\AnnouncerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncers extends ListRecords
{
    protected static string $resource = AnnouncerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
