<?php

namespace App\Filament\Pages\Analytics;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Actions\Analytics\SessionMetrics;
use App\Enums\AudienceMode;
use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\ReactionsChart;
use App\Filament\Analytics\Widgets\SessionLengthChart;
use App\Filament\Analytics\Widgets\VideoStats;
use App\Filament\Analytics\Widgets\WatchingChart;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * The video livestream: viewers, watch time, peak viewers and live reactions.
 */
class VideoAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'Video';

    protected static ?string $title = 'Video analytics';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'analytics/video';

    protected function exports(): array
    {
        return [
            'Daily figures, 30 days' => function (): array {
                $metrics = app(AnalyticsMetrics::class);

                return [
                    ['Date', 'Hours watched', 'Viewers', 'Fire reactions', 'Clap reactions', 'Heart reactions'],
                    array_map(fn (string $date, array $player, array $reactions): array => [
                        $date, $player['video_hours'], $player['video_people'],
                        $reactions['fire'] ?? 0, $reactions['clap'] ?? 0, $reactions['heart'] ?? 0,
                    ], $metrics->dates(30), $metrics->playerSeries(30), $metrics->reactionSeries(30)),
                ];
            },
            'Watching session lengths, 30 days' => function (): array {
                $buckets = app(SessionMetrics::class)->lengths(AudienceMode::Video, 30)['buckets'];

                return [['Length', 'Sessions'], array_map(null, array_keys($buckets), array_values($buckets))];
            },
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VideoStats::class,
            WatchingChart::class,
            SessionLengthChart::make(['mode' => 'video']),
            ReactionsChart::class,
        ];
    }
}
