<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Station;
use App\Support\PageMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ozmedianet.com: the OZ Media network landing page.
 */
class CorporateController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $stations = Station::query()->active()->orderBy('id')->get();

        return Inertia::render('corporate/home', [
            'meta' => PageMeta::make(
                'OZ Media | Voices, ideas, talent and community',
                'OZ Media is home to voices, ideas, talent and communities, from OZ Radio Bandung to OZ Bali.',
            ),
            'stations' => $stations->map(fn (Station $station): array => [
                'slug' => $station->slug,
                'name' => $station->name,
                'tagline' => $station->tagline,
            ]),
            // The station running the Ozubathon theme, for the spotlight (null: none).
            'ozubathon' => ($host = $stations->first(fn (Station $station): bool => $station->ozubathon() !== null))
                ? ['station' => ['slug' => $host->slug, 'name' => $host->name], ...$host->ozubathon(), 'now' => now()->toIso8601String()]
                : null,
            'stories' => PostResource::collection(
                Post::query()
                    ->published()
                    ->whereHas('station', fn (Builder $query) => $query->where('is_active', true))
                    ->with(['station', 'author'])
                    ->latest('published_at')
                    ->limit(4)
                    ->get()
            )->resolve($request),
        ]);
    }
}
