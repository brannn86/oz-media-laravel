<?php

namespace App\Filament\Analytics;

use App\Filament\NavigationGroup;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * An Analytics page: a grid of read-only widgets, network-wide, WIB days,
 * with an "Export CSV" menu of the data behind them (see exports()).
 *
 * Its widgets live in App\Filament\Analytics\Widgets, outside the
 * dashboard's auto-discovered folder, so they only show here.
 */
abstract class AnalyticsPage extends Page
{
    use HasPageShield;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Analytics;

    public function getHeaderWidgetsColumns(): int|array
    {
        return ['md' => 2];
    }

    /**
     * The page's downloadable datasets: label => fn () => [headers, rows].
     *
     * @return array<string, Closure(): array{0: list<string>, 1: iterable<array<int, scalar|null>>}>
     */
    abstract protected function exports(): array;

    protected function getHeaderActions(): array
    {
        $actions = [];

        foreach ($this->exports() as $label => $data) {
            $actions[] = Action::make('export_'.Str::slug($label, '_'))
                ->label($label)
                ->action(function () use ($label, $data): StreamedResponse {
                    [$headers, $rows] = $data();

                    return CsvExport::download($this->exportFilename($label), $headers, $rows);
                });
        }

        return $actions === [] ? [] : [
            ActionGroup::make($actions)
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->button(),
        ];
    }

    /** "oz-radio-analytics-most-played-songs-7-days-2026-11-25.csv" */
    public function exportFilename(string $label): string
    {
        return Str::slug('oz '.Str::snake(class_basename(static::class), ' ').' '.$label)
            .'-'.now('Asia/Jakarta')->toDateString().'.csv';
    }
}
