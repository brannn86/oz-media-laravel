<?php

namespace App\Filament\Resources\Stations;

use App\Filament\NavigationGroup;
use App\Filament\Pages\StationSettings;
use App\Filament\Resources\Stations\Pages\CreateStation;
use App\Filament\Resources\Stations\Pages\ListStations;
use App\Filament\Support\StationFields;
use App\Models\Station;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Station list + creation. Detailed editing lives on the Station Settings page.
 */
class StationResource extends Resource
{
    protected static ?string $model = Station::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Station;

    protected static ?string $navigationLabel = 'Stations & Live Mode';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        [$name, $slug] = StationFields::titleAndSlug('name', scopedToStation: false);

        return $schema
            ->columns(2)
            ->components([
                $name,
                $slug->helperText('Public URL segment, e.g. ozmedianet.com/oz-bandung. Leave blank to generate.'),
                TextInput::make('tagline')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->weight('medium')
                    ->searchable()
                    ->description(fn (Station $record): ?string => $record->tagline),
                TextColumn::make('slug')
                    ->prefix('/')
                    ->color('gray'),
                TextColumn::make('live_mode')
                    ->label('Live mode')
                    ->badge()
                    ->state(fn (Station $record): string => $record->hasVideoStream() ? 'Audio and Video' : 'Audio')
                    ->color(fn (Station $record): string => $record->hasVideoStream() ? 'danger' : 'gray')
                    ->icon(fn (Station $record): Heroicon => $record->hasVideoStream() ? Heroicon::VideoCamera : Heroicon::Radio)
                    ->tooltip('Video is offered when the station has a video stream URL; listeners pick radio or video.'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordUrl(null)
            ->recordActions([
                Action::make('settings')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->color('gray')
                    ->url(fn (Station $record): string => StationSettings::getUrl(['station' => $record->slug]))
                    ->visible(fn (): bool => StationSettings::canAccess()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStations::route('/'),
            'create' => CreateStation::route('/create'),
        ];
    }
}
