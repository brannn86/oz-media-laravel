<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\DeviceMetrics;
use Filament\Widgets\ChartWidget;

/**
 * Every Icecast listener by kind of player: the site, apps, smart speakers,
 * radio apps, cars. Needs the Icecast admin login.
 */
class IcecastPlayersChart extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'What everyone listens on (Icecast)';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public function getDescription(): string
    {
        if (app(DeviceMetrics::class)->icecastPlayers(7) === []) {
            return filled(config('oz.now_playing.icecast_admin_password'))
                ? 'Average listeners by kind of player over the last 7 days. The first sample comes within 15 minutes.'
                : 'Not set up: add the Icecast admin login (ICECAST_ADMIN_USER, ICECAST_ADMIN_PASSWORD) to .env to see smart speakers, radio apps and other players here.';
        }

        return 'Average listeners by kind of player at the 15-minute samples, last 7 days, from Icecast\'s listener list (by user agent).';
    }

    protected function getData(): array
    {
        $players = app(DeviceMetrics::class)->icecastPlayers(7);

        return [
            'datasets' => [
                ChartStyle::bars('Average listeners', array_column($players, 'average'), ChartStyle::NAVY),
                ChartStyle::bars('Highest', array_column($players, 'highest'), ChartStyle::YELLOW),
            ],
            'labels' => array_column($players, 'category'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return ChartStyle::countAxes();
    }
}
