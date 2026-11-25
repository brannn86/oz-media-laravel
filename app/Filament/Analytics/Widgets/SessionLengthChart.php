<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\SessionMetrics;
use App\Enums\AudienceMode;
use Filament\Widgets\ChartWidget;

/**
 * How long people stay once they press play: radio (mode "audio") or video.
 */
class SessionLengthChart extends ChartWidget
{
    /** Set when placing the widget: SessionLengthChart::make(['mode' => 'video']). */
    public string $mode = 'audio';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public function getHeading(): string
    {
        return $this->audienceMode() === AudienceMode::Video ? 'How long people watch' : 'How long people listen';
    }

    public function getDescription(): string
    {
        $lengths = app(SessionMetrics::class)->lengths($this->audienceMode(), 30);

        if ($lengths['sessions'] === 0) {
            return 'Finished sessions on the site, last 30 days. None yet.';
        }

        return number_format($lengths['sessions']).' finished sessions on the site, last 30 days. '
            .'Typical (median): '.$this->minutes($lengths['median']).'; average: '.$this->minutes($lengths['average']).'.';
    }

    protected function getData(): array
    {
        $buckets = app(SessionMetrics::class)->lengths($this->audienceMode(), 30)['buckets'];

        return [
            'datasets' => [
                ChartStyle::bars('Sessions', array_values($buckets), $this->audienceMode() === AudienceMode::Video ? ChartStyle::RED : ChartStyle::YELLOW),
            ],
            'labels' => array_keys($buckets),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            ...ChartStyle::countAxes(),
            'plugins' => ['legend' => ['display' => false]],
        ];
    }

    private function audienceMode(): AudienceMode
    {
        return AudienceMode::tryFrom($this->mode) ?? AudienceMode::Audio;
    }

    private function minutes(float $minutes): string
    {
        return $minutes >= 60
            ? intdiv((int) round($minutes), 60).' h '.((int) round($minutes) % 60).' min'
            : number_format($minutes, $minutes < 10 ? 1 : 0).' min';
    }
}
