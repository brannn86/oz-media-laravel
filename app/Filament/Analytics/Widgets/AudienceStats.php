<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AudienceStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $description = 'Accounts and active listeners count signed-in listeners (active: a minute of radio, the OZ Poin heartbeat). Tuned in counts everyone with the site\'s player on, guests included.';

    protected function getStats(): array
    {
        $metrics = app(AnalyticsMetrics::class);
        $accounts = $metrics->listenerAccounts();
        $google = $metrics->googleListeners();

        return [
            Stat::make('Listener accounts', number_format($accounts))
                ->description(number_format($metrics->newListeners(7)).' new this week, '.number_format($metrics->newListeners(30)).' in 30 days')
                ->descriptionIcon(Heroicon::UserPlus)
                ->color('primary'),
            Stat::make('Active listeners, 7 days', number_format($metrics->activeListeners(7)))
                ->description(number_format($metrics->activeListeners(1)).' today, '.number_format($metrics->activeListeners(30)).' in 30 days')
                ->descriptionIcon(Heroicon::Signal)
                ->color('success'),
            Stat::make('Tuned in on the site, 7 days', number_format($metrics->uniqueAudience(null, 7)))
                ->description(number_format($metrics->uniqueAudience(null, 1)).' today, guests included')
                ->descriptionIcon(Heroicon::GlobeAlt)
                ->color('success'),
            Stat::make('On a listening streak', number_format($metrics->listenersOnStreak()))
                ->description('Listened today or yesterday')
                ->descriptionIcon(Heroicon::Fire)
                ->color('warning'),
            Stat::make('Signed up with Google', $accounts > 0 ? round($google / $accounts * 100).'%' : '–')
                ->description(number_format($google).' of '.number_format($accounts).' accounts')
                ->descriptionIcon(Heroicon::Key),
        ];
    }
}
