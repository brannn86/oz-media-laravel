<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Filament\Support\StationFields;
use App\Models\Station;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Event')
                                    ->columns(2)
                                    ->schema([
                                        StationFields::select()->columnSpanFull(),
                                        ...StationFields::titleAndSlug(),
                                        Toggle::make('is_oz_event')
                                            ->label('Organised by OZ')
                                            ->helperText('OZ events are highlighted on the site. Off = community / partner event.')
                                            ->live()
                                            ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                                                if ($state && blank($get('organizer_name'))) {
                                                    $set('organizer_name', Station::query()->whereKey($get('station_id'))->value('name'));
                                                }
                                            })
                                            ->columnSpanFull(),
                                        TextInput::make('organizer_name')
                                            ->label('Organiser')
                                            ->maxLength(255),
                                        TextInput::make('location')
                                            ->maxLength(255),
                                        RichEditor::make('description')
                                            ->toolbarButtons([
                                                ['bold', 'italic', 'link'],
                                                ['h2', 'h3', 'bulletList', 'orderedList'],
                                                ['undo', 'redo'],
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Section::make('When (WIB)')
                                    ->schema([
                                        DateTimePicker::make('start_date')
                                            ->label('Starts')
                                            ->seconds(false)
                                            ->native(false)
                                            ->required()
                                            ->live(onBlur: true),
                                        DateTimePicker::make('end_date')
                                            ->label('Ends')
                                            ->seconds(false)
                                            ->native(false)
                                            ->after('start_date')
                                            ->helperText('Optional.'),
                                    ]),
                                Section::make('Tickets & poster')
                                    ->schema([
                                        TextInput::make('ticket_url')
                                            ->label('Ticket link')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://'),
                                        SpatieMediaLibraryFileUpload::make('poster')
                                            ->collection('poster')
                                            ->label('Poster')
                                            ->image()
                                            ->imageEditor()
                                            ->maxSize(4096),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
