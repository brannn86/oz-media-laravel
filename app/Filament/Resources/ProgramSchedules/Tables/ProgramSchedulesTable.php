<?php

namespace App\Filament\Resources\ProgramSchedules\Tables;

use App\Enums\DayOfWeek;
use App\Models\ProgramSchedule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramSchedulesTable
{
    public static function configure(Table $table, bool $withProgram = true): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('program.station'))
            ->defaultSort(fn (Builder $query) => $query->orderBy('day_of_week')->orderBy('start_time'))
            ->defaultGroup(
                Group::make('day_of_week')
                    ->label('Day')
                    ->getTitleFromRecordUsing(fn (ProgramSchedule $record): string => $record->day_of_week->getLabel())
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('day_of_week', $direction === 'desc' ? 'desc' : 'asc'))
                    ->collapsible(),
            )
            ->columns([
                TextColumn::make('start_time')
                    ->label('Time (WIB)')
                    ->formatStateUsing(fn (ProgramSchedule $record): string => $record->timeRangeLabel())
                    ->sortable(),
                ...($withProgram ? [
                    TextColumn::make('program.title')
                        ->label('Program')
                        ->searchable()
                        ->weight('medium'),
                    TextColumn::make('program.station.name')
                        ->label('Station')
                        ->badge()
                        ->color('gray')
                        ->toggleable(isToggledHiddenByDefault: true),
                ] : [
                    TextColumn::make('day_of_week')->label('Day'),
                ]),
            ])
            ->filters([
                SelectFilter::make('day_of_week')
                    ->label('Day')
                    ->options(DayOfWeek::class),
                ...($withProgram ? [
                    SelectFilter::make('station')
                        ->relationship('program.station', 'name')
                        ->preload(),
                ] : []),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
