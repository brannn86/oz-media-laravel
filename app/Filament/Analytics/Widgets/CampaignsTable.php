<?php

namespace App\Filament\Analytics\Widgets;

use App\Actions\Analytics\AcquisitionMetrics;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Campaigns: sessions from links tagged with utm_campaign.
 */
class CampaignsTable extends TableWidget
{
    protected static ?string $heading = 'Campaigns, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->description('Tag links with utm_source, utm_medium and utm_campaign, e.g. ?utm_source=instagram&utm_medium=story&utm_campaign=konser-amal.')
            ->records(fn (): array => collect(app(AcquisitionMetrics::class)->campaigns(30))
                ->keyBy(fn (array $row): string => md5($row['campaign']."\0".$row['source']."\0".$row['medium']))
                ->all())
            ->paginated(false)
            ->columns([
                TextColumn::make('campaign')
                    ->weight('medium')
                    ->description(fn (array $record): string => $record['source'].($record['medium'] !== null ? " · {$record['medium']}" : '')),
                TextColumn::make('sessions')->numeric()->alignEnd(),
                TextColumn::make('people')->label('Listeners')->numeric()->alignEnd(),
                TextColumn::make('hours')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' h')
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No tagged links yet')
            ->emptyStateDescription('Add UTM tags to the links you share to see each campaign here.');
    }
}
