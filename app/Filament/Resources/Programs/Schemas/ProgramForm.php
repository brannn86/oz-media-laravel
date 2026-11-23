<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Filament\Support\StationFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Program details')
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                StationFields::select()->columnSpanFull(),
                                ...StationFields::titleAndSlug(),
                                Textarea::make('description')
                                    ->rows(5)
                                    ->columnSpanFull(),
                                Select::make('announcers')
                                    ->label('Hosted by')
                                    ->relationship(
                                        'announcers',
                                        'name',
                                        fn (Builder $query, Get $get) => $query->where('station_id', $get('station_id')),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->displayName())
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Publishing')
                            ->columnSpan(1)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->helperText('Inactive programs are hidden from the public schedule.')
                                    ->default(true),
                                SpatieMediaLibraryFileUpload::make('cover')
                                    ->collection('cover')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(4096),
                            ]),
                    ]),
            ]);
    }
}
