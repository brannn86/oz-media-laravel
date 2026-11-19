<?php

namespace App\Filament\Support;

use App\Models\Station;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

/**
 * Reusable station-aware form fields, columns and filters for station-owned resources.
 */
class StationFields
{
    public static function select(): Select
    {
        return Select::make('station_id')
            ->label('Station')
            ->relationship('station', 'name')
            ->default(fn (): ?int => Station::query()->active()->orderBy('id')->value('id'))
            ->required()
            ->live()
            ->preload()
            ->native(false);
    }

    public static function column(): TextColumn
    {
        return TextColumn::make('station.name')
            ->label('Station')
            ->badge()
            ->color('gray')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: Station::query()->count() <= 1);
    }

    public static function filter(): SelectFilter
    {
        return SelectFilter::make('station')
            ->relationship('station', 'name')
            ->preload();
    }

    /**
     * A title (or name) input that fills the slug while the record is new,
     * plus the slug input itself, unique within the selected station.
     *
     * @return array{0: TextInput, 1: TextInput}
     */
    public static function titleAndSlug(string $source = 'title', bool $scopedToStation = true): array
    {
        return [
            TextInput::make($source)
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state, string $operation): void {
                    // Only follow the title while the slug was never edited by hand.
                    if ($operation === 'create' || blank($get('slug')) || $get('slug') === Str::slug((string) $old)) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')
                ->maxLength(255)
                ->alphaDash()
                ->helperText('Used in the URL. Leave blank to generate it automatically.')
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $scopedToStation
                        ? $rule->where('station_id', $get('station_id'))
                        : $rule,
                ),
        ];
    }
}
