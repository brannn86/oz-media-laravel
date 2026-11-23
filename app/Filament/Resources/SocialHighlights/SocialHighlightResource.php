<?php

namespace App\Filament\Resources\SocialHighlights;

use App\Enums\SocialPlatform;
use App\Filament\NavigationGroup;
use App\Filament\Resources\SocialHighlights\Pages\ManageSocialHighlights;
use App\Filament\Support\StationFields;
use App\Models\SocialHighlight;
use BackedEnum;
use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

class SocialHighlightResource extends Resource
{
    protected static ?string $model = SocialHighlight::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::SocialAndEvents;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                StationFields::select()->columnSpanFull(),
                TextInput::make('post_url')
                    ->label('Post URL')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('https://www.tiktok.com/@ozradiobandung/video/…')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($platform = SocialPlatform::detectFromUrl($state)) {
                            $set('platform', $platform);
                        }
                    })
                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        $platform = $get('platform');
                        $platform = $platform instanceof SocialPlatform ? $platform : SocialPlatform::tryFrom((string) $platform);

                        if ($platform && SocialPlatform::detectFromUrl($value) !== $platform) {
                            $fail("This doesn't look like a {$platform->getLabel()} link.");
                        }
                    })
                    ->columnSpanFull(),
                Select::make('platform')
                    ->options(SocialPlatform::class)
                    ->required()
                    ->native(false)
                    ->helperText('Detected automatically from the URL.'),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                SpatieMediaLibraryFileUpload::make('thumbnail')
                    ->collection('thumbnail')
                    ->image()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->helperText('Optional. Overrides the platform thumbnail.'),
                Grid::make(1)->schema([
                    Toggle::make('is_featured')
                        ->label('Featured on homepage'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first. You can also drag to reorder in the table.'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->collection('thumbnail')
                    ->conversion('thumb')
                    ->label('')
                    ->imageWidth(64)
                    ->imageHeight(64),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    ->url(fn (SocialHighlight $record): string => $record->post_url, shouldOpenInNewTab: true),
                TextColumn::make('platform')
                    ->badge(),
                ToggleColumn::make('is_featured')
                    ->label('Featured'),
                StationFields::column(),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')->options(SocialPlatform::class),
                TernaryFilter::make('is_featured')->label('Featured'),
                StationFields::filter(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('feature')
                        ->label('Mark as featured')
                        ->icon(Heroicon::OutlinedStar)
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unfeature')
                        ->label('Remove from featured')
                        ->icon(Heroicon::OutlinedXMark)
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSocialHighlights::route('/'),
        ];
    }
}
