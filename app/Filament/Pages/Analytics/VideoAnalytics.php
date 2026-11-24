<?php

namespace App\Filament\Pages\Analytics;

use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\ReactionsChart;
use App\Filament\Analytics\Widgets\VideoStats;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * The video livestream: which stations offer it, and live reactions.
 */
class VideoAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'Video';

    protected static ?string $title = 'Video analytics';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'analytics/video';

    protected function getHeaderWidgets(): array
    {
        return [
            VideoStats::class,
            ReactionsChart::class,
        ];
    }
}
