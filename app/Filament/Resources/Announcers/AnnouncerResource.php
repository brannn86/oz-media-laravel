<?php

namespace App\Filament\Resources\Announcers;

use App\Filament\NavigationGroup;
use App\Filament\Resources\Announcers\Pages\CreateAnnouncer;
use App\Filament\Resources\Announcers\Pages\EditAnnouncer;
use App\Filament\Resources\Announcers\Pages\ListAnnouncers;
use App\Filament\Resources\Announcers\Schemas\AnnouncerForm;
use App\Filament\Resources\Announcers\Tables\AnnouncersTable;
use App\Models\Announcer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AnnouncerResource extends Resource
{
    protected static ?string $model = Announcer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Programming;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AnnouncerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncersTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'stage_name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncers::route('/'),
            'create' => CreateAnnouncer::route('/create'),
            'edit' => EditAnnouncer::route('/{record}/edit'),
        ];
    }
}
