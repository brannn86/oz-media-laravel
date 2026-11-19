<?php

namespace App\Filament\Resources\Announcers\Schemas;

use App\Filament\Support\StationFields;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AnnouncerForm
{
    public static function configure(Schema $schema): Schema
    {
        [$name, $slug] = StationFields::titleAndSlug('name');

        return $schema
            ->components([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Profile')
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                StationFields::select()->columnSpanFull(),
                                $name->label('Real name'),
                                TextInput::make('stage_name')
                                    ->label('On-air name')
                                    ->maxLength(255),
                                $slug->columnSpanFull(),
                                Textarea::make('bio')
                                    ->rows(5)
                                    ->columnSpanFull(),
                                Select::make('programs')
                                    ->label('Hosts programs')
                                    ->relationship(
                                        'programs',
                                        'title',
                                        fn (Builder $query, Get $get) => $query->where('station_id', $get('station_id')),
                                    )
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull(),
                            ]),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Section::make()
                                    ->schema([
                                        FileUpload::make('avatar')
                                            ->image()
                                            ->avatar()
                                            ->imageEditor()
                                            ->circleCropper()
                                            ->directory('announcers')
                                            ->maxSize(2048),
                                        Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true),
                                    ]),
                                Section::make('Social links')
                                    ->schema([
                                        TextInput::make('social_links.instagram')
                                            ->label('Instagram')
                                            ->url()
                                            ->prefixIcon('heroicon-o-camera'),
                                        TextInput::make('social_links.tiktok')
                                            ->label('TikTok')
                                            ->url()
                                            ->prefixIcon('heroicon-o-musical-note'),
                                        TextInput::make('social_links.x')
                                            ->label('X')
                                            ->url()
                                            ->prefixIcon('heroicon-o-at-symbol'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
