<?php

namespace App\Filament\Resources\Announcers\Pages;

use App\Filament\Resources\Announcers\AnnouncerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncer extends EditRecord
{
    protected static string $resource = AnnouncerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
