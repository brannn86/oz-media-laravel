<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Actions\Posts\PublishPost;
use App\Enums\PostStatus;
use App\Filament\Support\StationFields;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['author', 'station']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('')
                    ->imageWidth(80)
                    ->imageHeight(45),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium')
                    ->description(fn (Post $record): ?string => str($record->excerpt)->limit(80)->toString() ?: null),
                TextColumn::make('status')
                    ->badge()
                    ->description(fn (Post $record): ?string => $record->status === PostStatus::Published && $record->published_at?->isFuture()
                        ? 'Scheduled'
                        : null),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->toggleable(),
                StationFields::column(),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(PostStatus::class),
                SelectFilter::make('author')->relationship('author', 'name'),
                StationFields::filter(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('publish')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn (Post $record): bool => $record->status === PostStatus::Draft && auth()->user()?->can('update', $record))
                        ->requiresConfirmation()
                        ->action(fn (Post $record, PublishPost $publish) => $publish->handle($record))
                        ->successNotificationTitle('Article published'),
                    Action::make('unpublish')
                        ->label('Move to drafts')
                        ->icon(Heroicon::OutlinedArrowUturnLeft)
                        ->color('gray')
                        ->visible(fn (Post $record): bool => $record->status === PostStatus::Published && auth()->user()?->can('update', $record))
                        ->requiresConfirmation()
                        ->action(fn (Post $record, PublishPost $publish) => $publish->unpublish($record))
                        ->successNotificationTitle('Article moved to drafts'),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
