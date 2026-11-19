<?php

namespace App\Filament\Resources\Events\Tables;

use App\Filament\Support\StationFields;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_date')
            ->columns([
                ImageColumn::make('poster_image')
                    ->label('')
                    ->imageWidth(48)
                    ->imageHeight(64),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium')
                    ->description(fn (Event $record): ?string => $record->location),
                TextColumn::make('start_date')
                    ->label('Date (WIB)')
                    ->dateTime('D, d M Y · H:i')
                    ->sortable(),
                TextColumn::make('organizer_name')
                    ->label('Organiser')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_oz_event')
                    ->label('OZ event')
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('ticket_url')
                    ->label('Tickets')
                    ->icon(fn (?string $state): ?string => $state ? 'heroicon-o-ticket' : null)
                    ->url(fn (Event $record): ?string => $record->ticket_url, shouldOpenInNewTab: true)
                    ->alignCenter()
                    ->toggleable(),
                StationFields::column(),
            ])
            ->filters([
                TernaryFilter::make('is_oz_event')->label('OZ event'),
                StationFields::filter(),
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
