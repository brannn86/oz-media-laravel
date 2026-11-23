<?php

namespace App\Filament\Resources\Announcers\Tables;

use App\Filament\Support\StationFields;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AnnouncersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('programs'))
            ->defaultSort('name')
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->label('')
                    ->circular(),
                TextColumn::make('stage_name')
                    ->label('On-air name')
                    ->default('—')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('name')
                    ->label('Real name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('programs.title')
                    ->label('Programs')
                    ->badge()
                    ->limitList(2),
                StationFields::column(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                StationFields::filter(),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
