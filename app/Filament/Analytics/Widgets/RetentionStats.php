<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AcquisitionMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetentionStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Do listeners come back?';

    protected ?string $description = 'Everyone with the site\'s player on, guests included (one device = one listener). Counted from 24 Nov 2026.';

    protected function getStats(): array
    {
        $metrics = app(AcquisitionMetrics::class);

        return [
            $this->rate('Back within 7 days', $metrics->returnRate(7), 7),
            $this->rate('Back within 30 days', $metrics->returnRate(30), 30),
        ];
    }

    /**
     * @param  array{cohort: int, returned: int, rate: float|null}  $rate
     */
    private function rate(string $label, array $rate, int $days): Stat
    {
        return Stat::make($label, $rate['rate'] === null ? '–' : number_format($rate['rate'], 1).'%')
            ->description($rate['rate'] === null
                ? "Needs listeners who first tuned in {$days}+ days ago"
                : number_format($rate['returned']).' of '.number_format($rate['cohort']).' new listeners came back')
            ->descriptionIcon(Heroicon::ArrowPath)
            ->color($rate['rate'] === null ? 'gray' : 'primary');
    }
}
