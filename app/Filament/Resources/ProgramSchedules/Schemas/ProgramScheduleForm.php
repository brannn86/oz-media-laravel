<?php

namespace App\Filament\Resources\ProgramSchedules\Schemas;

use App\Enums\DayOfWeek;
use App\Models\ProgramSchedule;
use App\Rules\ScheduleSlotIsFree;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Used by both the standalone Schedule resource and the Program "Schedules" relation manager.
 */
class ProgramScheduleForm
{
    public static function configure(Schema $schema, bool $withProgram = true): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                ...($withProgram ? [
                    Select::make('program_id')
                        ->relationship('program', 'title')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->title} · {$record->station->name}")
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                ] : []),
                Select::make('day_of_week')
                    ->label('Day')
                    ->options(DayOfWeek::class)
                    ->required()
                    ->live()
                    ->native(false),
                TimePicker::make('start_time')
                    ->label('Starts (WIB)')
                    ->seconds(false)
                    ->required()
                    ->live(onBlur: true),
                TimePicker::make('end_time')
                    ->label('Ends (WIB)')
                    ->seconds(false)
                    ->required()
                    ->helperText('Use 00:00 for midnight.')
                    ->rule(fn (Get $get, ?ProgramSchedule $record, $livewire): ScheduleSlotIsFree => new ScheduleSlotIsFree(
                        programId: $get('program_id') ?? ($livewire instanceof RelationManager ? $livewire->getOwnerRecord()->getKey() : null),
                        day: $get('day_of_week'),
                        start: $get('start_time'),
                        ignoreScheduleId: $record?->getKey(),
                    )),
            ]);
    }
}
