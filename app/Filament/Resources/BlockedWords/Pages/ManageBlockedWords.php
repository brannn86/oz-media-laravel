<?php

namespace App\Filament\Resources\BlockedWords\Pages;

use App\Filament\Resources\BlockedWords\BlockedWordResource;
use App\Support\ProfanityFilter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageBlockedWords extends ManageRecords
{
    protected static string $resource = BlockedWordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testFilter')
                ->label('Test a message')
                ->icon(Heroicon::OutlinedBeaker)
                ->color('gray')
                ->modalHeading('Test the filter')
                ->modalDescription('See how a chat message would look after filtering. Nothing is posted.')
                ->modalSubmitActionLabel('Check')
                ->schema([
                    Textarea::make('message')
                        ->required()
                        ->rows(3)
                        ->maxLength(280),
                ])
                ->action(function (array $data, ProfanityFilter $filter, Action $action): void {
                    $cleaned = $filter->clean($data['message']);

                    Notification::make()
                        ->title($cleaned === $data['message'] ? 'Nothing would be masked' : 'Listeners would see:')
                        ->body($cleaned === $data['message'] ? null : $cleaned)
                        ->color($cleaned === $data['message'] ? 'success' : 'warning')
                        ->send();

                    // Keep the modal open so moderators can try another message.
                    $action->halt();
                }),
            CreateAction::make()->label('Add word'),
        ];
    }
}
