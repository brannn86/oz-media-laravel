<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Support\PageMeta;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ozmedianet.com: the OZ Media network landing page.
 */
class CorporateController extends Controller
{
    public function __invoke(): Response
    {
        $stations = Station::query()->active()->orderBy('id')->get();

        return Inertia::render('corporate/home', [
            'meta' => PageMeta::make(
                'OZ Media | Above and Beyond',
                'OZ Media is home to voices, ideas, talent and communities, from OZ Radio Bandung to OZ Bali.',
            ),
            'stations' => $stations->map(fn (Station $station): array => [
                'slug' => $station->slug,
                'name' => $station->name,
                'tagline' => $station->tagline,
            ]),
            // The station running the Ozubathon theme, for its sticker (null: none).
            'ozubathon' => ($host = $stations->first(fn (Station $station): bool => $station->ozubathon() !== null))
                ? ['station' => ['slug' => $host->slug, 'name' => $host->name], ...$host->ozubathon(), 'now' => now()->toIso8601String()]
                : null,
        ]);
    }
}
