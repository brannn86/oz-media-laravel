<?php

namespace App\Filament\Resources\BlockedWords;

use App\Filament\NavigationGroup;
use App\Filament\Resources\BlockedWords\Pages\ManageBlockedWords;
use App\Models\BlockedWord;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The live chat's profanity list. Changes apply to the next message sent.
 */
class BlockedWordResource extends Resource
{
    protected static ?string $model = BlockedWord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::LiveChat;

    protected static ?string $navigationLabel = 'Blocked Words';

    protected static ?string $modelLabel = 'blocked word';

    protected static ?string $slug = 'chat/blocked-words';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'word';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('word')
                    ->required()
                    ->minLength(2)
                    ->maxLength(50)
                    // Letters, numbers and single spaces between words (surrounding spaces are trimmed).
                    ->regex('/^\s*[\p{L}\p{N}]+(?: [\p{L}\p{N}]+)*\s*$/u')
                    ->validationMessages([
                        'regex' => 'Use letters and numbers only (spaces allowed between words).',
                    ])
                    ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state)))
                    // Words are stored lower-case, so compare that way ("Anjing" = "anjing").
                    ->rule(fn (?BlockedWord $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        $exists = BlockedWord::query()
                            ->where('word', mb_strtolower(trim((string) $value)))
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($exists) {
                            $fail('This word is already on the list.');
                        }
                    })
                    ->helperText('Matched as a whole word, in any letter case, even with stretched letters ("anjiiing"). It is masked, not removed: "a*****".')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('word')
            ->paginated([50, 100, 'all'])
            ->columns([
                TextColumn::make('word')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No blocked words')
            ->emptyStateDescription('Chat messages are not filtered. Add words to mask them.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBlockedWords::route('/'),
        ];
    }
}
