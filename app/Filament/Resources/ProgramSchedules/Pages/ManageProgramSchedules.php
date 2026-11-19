<?php

namespace App\Filament\Resources\ProgramSchedules\Pages;

use App\Filament\Resources\ProgramSchedules\ProgramScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProgramSchedules extends ManageRecords
{
    protected static string $resource = ProgramScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add slot'),
        ];
    }
}
