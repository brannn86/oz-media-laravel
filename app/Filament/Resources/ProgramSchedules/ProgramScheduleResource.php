<?php

namespace App\Filament\Resources\ProgramSchedules;

use App\Filament\NavigationGroup;
use App\Filament\Resources\ProgramSchedules\Pages\ManageProgramSchedules;
use App\Filament\Resources\ProgramSchedules\Schemas\ProgramScheduleForm;
use App\Filament\Resources\ProgramSchedules\Tables\ProgramSchedulesTable;
use App\Models\ProgramSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProgramScheduleResource extends Resource
{
    protected static ?string $model = ProgramSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Programming;

    protected static ?string $navigationLabel = 'Weekly Schedule';

    protected static ?string $modelLabel = 'schedule slot';

    protected static ?string $slug = 'schedule';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ProgramScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramSchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProgramSchedules::route('/'),
        ];
    }
}
