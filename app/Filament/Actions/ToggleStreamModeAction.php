<?php

namespace App\Filament\Actions;

use App\Actions\Stations\SetStreamMode;
use App\Models\Station;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Switches a station between Audio Only and Video Livestream.
 *
 * Guarded by the `ToggleVideoLive:Station` permission (StationPolicy::toggleVideoLive).
 */
class ToggleStreamModeAction
{
    public static function make(?Station $station = null): Action
    {
        $resolve = fn (?Station $record): ?Station => $station ?? $record;

        return Action::make('toggleStreamMode')
            ->label(fn (?Station $record): string => $resolve($record)?->is_video_live ? 'Switch to Audio Only' : 'Go Live on Video')
            ->icon(fn (?Station $record): Heroicon => $resolve($record)?->is_video_live ? Heroicon::OutlinedRadio : Heroicon::OutlinedVideoCamera)
            ->color(fn (?Station $record): string => $resolve($record)?->is_video_live ? 'gray' : 'danger')
            ->visible(fn (?Station $record): bool => (bool) auth()->user()?->can('toggleVideoLive', $resolve($record)))
            ->requiresConfirmation()
            ->modalHeading(fn (?Station $record): string => $resolve($record)?->is_video_live
                ? 'Switch to Audio Only?'
                : 'Switch to Video Livestream?')
            ->modalDescription(fn (?Station $record): string => $resolve($record)?->is_video_live
                ? 'The website player will go back to the audio stream for all listeners.'
                : 'The website player will switch to the video stream for all listeners.')
            ->action(function (?Station $record, SetStreamMode $setStreamMode, Action $action) use ($resolve): void {
                $target = $resolve($record);

                try {
                    $setStreamMode->toggle($target);
                } catch (ValidationException $e) {
                    Notification::make()
                        ->title('Cannot go live on video')
                        ->body(collect($e->errors())->flatten()->first())
                        ->danger()
                        ->send();

                    $action->halt();
                }

                Notification::make()
                    ->title($target->is_video_live ? "{$target->name} is now live on video" : "{$target->name} is back to audio only")
                    ->success()
                    ->send();
            });
    }
}
