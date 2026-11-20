<?php

namespace App\Filament\Resources\Rewards;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Rewards\Pages\ManageRewards;
use App\Models\Reward;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Merch, concert tickets and other prizes listeners redeem with OZ Poin.
 */
class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Loyalty;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(3)
                    ->helperText('What listeners get and how they collect it.')
                    ->columnSpanFull(),
                TextInput::make('points_cost')
                    ->label('Cost (OZ Poin)')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('stock')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Leave empty for unlimited.'),
                Select::make('station_id')
                    ->label('Station')
                    ->relationship('station', 'name')
                    ->placeholder('All stations')
                    ->helperText('Leave empty to offer it on every station.'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                FileUpload::make('image')
                    ->image()
                    ->imageEditor()
                    ->directory('rewards')
                    ->maxSize(4096),
                Toggle::make('is_active')
                    ->label('Available to redeem')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')->label('')->square()->imageSize(48),
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('points_cost')->label('Cost')->suffix(' poin')->sortable(),
                TextColumn::make('stock')->placeholder('Unlimited')->sortable(),
                TextColumn::make('redemptions_count')->counts('redemptions')->label('Redeemed'),
                TextColumn::make('station.name')->label('Station')->placeholder('All stations')->badge()->color('gray'),
                ToggleColumn::make('is_active')->label('Available'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Reward $record): bool => $record->redemptions()->exists())
                    ->tooltip('Rewards with redemptions can only be switched off.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRewards::route('/'),
        ];
    }
}
