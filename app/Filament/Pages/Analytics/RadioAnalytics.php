<?php

namespace App\Filament\Pages\Analytics;

use App\Actions\Analytics\AnalyticsMetrics;
use App\Actions\Analytics\SessionMetrics;
use App\Enums\AudienceMode;
use App\Filament\Analytics\AnalyticsPage;
use App\Filament\Analytics\Widgets\ListenersByHourChart;
use App\Filament\Analytics\Widgets\ListeningChart;
use App\Filament\Analytics\Widgets\RadioListenersChart;
use App\Filament\Analytics\Widgets\RadioStats;
use App\Filament\Analytics\Widgets\SessionLengthChart;
use App\Filament\Analytics\Widgets\SongDropOffTable;
use App\Filament\Analytics\Widgets\TopSongsTable;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * The radio stream: Icecast and website listeners, listening time, songs and requests.
 */
class RadioAnalytics extends AnalyticsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRadio;

    protected static ?string $navigationLabel = 'Radio';

    protected static ?string $title = 'Radio analytics';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'analytics/radio';

    protected function exports(): array
    {
        return [
            'Daily figures, 30 days' => function (): array {
                $metrics = app(AnalyticsMetrics::class);

                return [
                    ['Date', 'Icecast average (song starts)', 'Icecast highest (song starts)', 'Website peak at once', 'Hours listened on the site', 'Listeners on the site'],
                    array_map(fn (string $date, array $icecast, array $website, array $player): array => [
                        $date, $icecast['average'], $icecast['highest'], $website['peak'], $player['audio_hours'], $player['audio_people'],
                    ], $metrics->dates(30), $metrics->icecastSeries(30), $metrics->websitePeakSeries(30), $metrics->playerSeries(30)),
                ];
            },
            'Listeners by hour of day, 7 days' => function (): array {
                $byHour = app(AnalyticsMetrics::class)->listenersByHour(7);

                return [
                    ['Hour (WIB)', 'Icecast average', 'Website average'],
                    array_map(fn (int $hour): array => [sprintf('%02d:00', $hour), $byHour['icecast'][$hour], $byHour['website'][$hour]], range(0, 23)),
                ];
            },
            'Most played songs, 7 days' => fn (): array => [
                ['Title', 'Artist', 'Plays', 'Avg. Icecast listeners'],
                array_map(fn (array $row): array => array_values($row), app(AnalyticsMetrics::class)->topSongs(7)),
            ],
            'Songs people tune out of, 30 days' => fn (): array => [
                ['Title', 'Artist', 'Plays', 'Tune-outs', 'Tune-outs per play'],
                array_map(fn (array $row): array => [$row['title'], $row['artist'], $row['plays'], $row['tune_outs'], $row['per_play']], app(SessionMetrics::class)->songDropOff(30, PHP_INT_MAX)),
            ],
            'Listening session lengths, 30 days' => function (): array {
                $buckets = app(SessionMetrics::class)->lengths(AudienceMode::Audio, 30)['buckets'];

                return [['Length', 'Sessions'], array_map(null, array_keys($buckets), array_values($buckets))];
            },
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RadioStats::class,
            ListeningChart::class,
            ListenersByHourChart::class,
            RadioListenersChart::class,
            SessionLengthChart::make(['mode' => 'audio']),
            SongDropOffTable::class,
            TopSongsTable::class,
        ];
    }
}
