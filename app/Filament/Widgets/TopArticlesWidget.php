<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Most-read OZ News articles over the last 7 WIB days.
 */
class TopArticlesWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Top OZ News, last 7 days';

    public function table(Table $table): Table
    {
        $since = now('Asia/Jakarta')->subDays(6)->toDateString();

        return $table
            ->query(
                Post::query()
                    ->with('station')
                    ->withSum(['dailyViews as views_7d' => fn (Builder $query) => $query->where('date', '>=', $since)], 'views')
                    ->whereHas('dailyViews', fn (Builder $query) => $query->where('date', '>=', $since))
                    ->orderByDesc('views_7d')
            )
            ->paginated(false)
            ->modifyQueryUsing(fn (Builder $query) => $query->limit(5))
            ->columns([
                TextColumn::make('title')
                    ->wrap()
                    ->weight('medium')
                    ->description(fn (Post $record): ?string => $record->station?->name)
                    ->url(fn (Post $record): ?string => PostResource::canEdit($record) ? PostResource::getUrl('edit', ['record' => $record]) : null),
                TextColumn::make('views_7d')
                    ->label('Views')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->emptyStateHeading('No article views yet')
            ->emptyStateDescription('Views are counted when listeners open an article.');
    }
}
