<?php

namespace App\Filament\Resources\SocialHighlights\Pages;

use App\Filament\Resources\SocialHighlights\SocialHighlightResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSocialHighlights extends ManageRecords
{
    protected static string $resource = SocialHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
