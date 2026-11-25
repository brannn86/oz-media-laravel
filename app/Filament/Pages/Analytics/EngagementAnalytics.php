<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\EngagementChart;
use App\Filament\Analytics\Widgets\EngagementStats;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * Taking part: chat, polls, song requests and OZ Poin.
 */
class EngagementAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Engagement';

    protected static ?string $title = 'Engagement analytics';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'analytics/engagement';

    protected function getHeaderWidgets(): array
    {
        return [
            EngagementStats::class,
            EngagementChart::class,
        ];
    }
}
