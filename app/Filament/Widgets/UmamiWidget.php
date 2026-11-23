<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Self-hosted Umami web analytics, embedded from its share link
 * (UMAMI_SHARE_URL). Shows setup steps until that is configured.
 */
class UmamiWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.umami';

    public function shareUrl(): ?string
    {
        $url = config('oz.analytics.umami.share_url');

        // Only http(s) links may be framed.
        return is_string($url) && preg_match('#^https?://#i', $url) ? $url : null;
    }
}
