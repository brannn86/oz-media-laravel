<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\RequestMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RequestStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Song requests vs airplay, last 30 days';

    protected ?string $description = 'Played: the stream played it (matched automatically) or a moderator marked it played. Wait: from the request to that moment.';

    protected function getStats(): array
    {
        $summary = app(RequestMetrics::class)->summary(30);

        return [
            Stat::make('Requests played', $summary['play_rate'] === null ? '–' : number_format($summary['play_rate'], 0).'%')
                ->description(number_format($summary['played']).' played, '.number_format($summary['rejected']).' rejected, of '.number_format($summary['submitted']).' requests')
                ->descriptionIcon(Heroicon::MusicalNote)
                ->color('primary'),
            Stat::make('Typical wait to air', $this->minutes($summary['median_wait_minutes']))
                ->description('Average '.$this->minutes($summary['average_wait_minutes']))
                ->descriptionIcon(Heroicon::Clock),
            Stat::make('Still waiting', number_format($summary['waiting']))
                ->description('Requested in the last 30 days, not played or rejected yet')
                ->descriptionIcon(Heroicon::QueueList)
                ->color($summary['waiting'] > 0 ? 'warning' : 'gray'),
        ];
    }

    private function minutes(?float $minutes): string
    {
        return match (true) {
            $minutes === null => '–',
            $minutes >= 60 => intdiv((int) round($minutes), 60).' h '.((int) round($minutes) % 60).' min',
            default => number_format($minutes, 0).' min',
        };
    }
}
