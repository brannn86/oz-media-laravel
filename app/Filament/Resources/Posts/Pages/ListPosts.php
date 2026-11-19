<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Write article'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'drafts' => Tab::make('Drafts')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', PostStatus::Draft)),
            'published' => Tab::make('Live')
                ->modifyQueryUsing(fn (Builder $query) => $query->scopes('published')),
            'scheduled' => Tab::make('Scheduled')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', PostStatus::Published)
                    ->where('published_at', '>', now())),
        ];
    }
}
