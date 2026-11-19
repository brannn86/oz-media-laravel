<?php

namespace App\Filament\Resources\Users;

use App\Enums\AdminRole;
use App\Filament\NavigationGroup;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Access;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep the current password.' : null),
                    ]),
                Section::make('Admin role')
                    ->description('Users without a role cannot sign in to the admin panel.')
                    ->schema([
                        CheckboxList::make('roles')
                            ->hiddenLabel()
                            ->relationship(
                                'roles',
                                'name',
                                fn (Builder $query) => $query->whereIn('name', AdminRole::values()),
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record): string => AdminRole::tryFrom($record->name)?->getLabel() ?? $record->name)
                            ->descriptions([
                                AdminRole::Dev->value => 'Full access: all content, stations, users and roles.',
                                AdminRole::Editorial->value => 'Programs, schedules, announcers and OZ News.',
                                AdminRole::SocialMedia->value => 'Social highlights, events and the video livestream toggle.',
                            ])
                            ->bulkToggleable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AdminRole::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn (string $state): string => AdminRole::tryFrom($state)?->getColor() ?? 'gray')
                    ->placeholder('No access'),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name', fn (Builder $query) => $query->whereIn('name', AdminRole::values()))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => AdminRole::tryFrom($record->name)?->getLabel() ?? $record->name),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
