<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use App\Filament\Resources\ProgramSchedules\Schemas\ProgramScheduleForm;
use App\Filament\Resources\ProgramSchedules\Tables\ProgramSchedulesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Weekly schedule (WIB)';

    public function form(Schema $schema): Schema
    {
        return ProgramScheduleForm::configure($schema, withProgram: false);
    }

    public function table(Table $table): Table
    {
        return ProgramSchedulesTable::configure($table, withProgram: false)
            ->defaultGroup(null)
            ->headerActions([
                CreateAction::make()->label('Add slot'),
            ]);
    }
}
