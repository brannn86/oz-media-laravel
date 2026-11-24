<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Filament\Resources\RewardRedemptions\RewardRedemptionResource;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EngagementStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $metrics = app(AnalyticsMetrics::class);
        $points = $metrics->points(7);
        $requests = $metrics->songRequests(7);
        $pending = $metrics->pendingRedemptions();

        return [
            Stat::make('Chat messages, 7 days', number_format($metrics->chatMessages(7)))
                ->description(number_format($metrics->chatMessages(1)).' today')
                ->descriptionIcon(Heroicon::ChatBubbleLeftRight)
                ->color('primary'),
            Stat::make('Poll votes, 7 days', number_format($metrics->pollVotes(7)))
                ->description(number_format($metrics->pollVotes(30)).' in 30 days')
                ->descriptionIcon(Heroicon::ChartBar),
            Stat::make('Song requests, 7 days', number_format($requests['submitted']))
                ->description(number_format($requests['played']).' played on air')
                ->descriptionIcon(Heroicon::MusicalNote),
            Stat::make('OZ Poin earned, 7 days', number_format($points['earned']))
                ->description(number_format($points['spent']).' spent on rewards; '.number_format($pending).' '.($pending === 1 ? 'redemption' : 'redemptions').' waiting')
                ->descriptionIcon(Heroicon::Gift)
                ->color($pending > 0 ? 'warning' : 'gray')
                ->url(RewardRedemptionResource::canViewAny() ? RewardRedemptionResource::getUrl() : null),
        ];
    }
}
