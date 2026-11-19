<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Station;
use App\Support\MediaUrl;
use App\Support\PageMeta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StationNewsController extends Controller
{
    public function index(Station $station): Response
    {
        $posts = $station->posts()
            ->published()
            ->with('author')
            ->latest('published_at')
            ->paginate(9);

        return Inertia::render('station/news/index', [
            'meta' => PageMeta::make("OZ News | {$station->name}", "Latest stories from {$station->name}."),
            'posts' => PostResource::collection($posts),
        ]);
    }

    public function show(Request $request, Station $station, string $slug): Response
    {
        $post = $station->posts()->published()->with('author')->where('slug', $slug)->firstOrFail();

        $more = $station->posts()
            ->published()
            ->whereKeyNot($post->getKey())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('station/news/show', [
            'meta' => (new PageMeta(
                title: ($post->meta_title ?: $post->title)." | {$station->name}",
                description: $post->meta_description ?: $post->excerpt ?: $post->body,
                image: MediaUrl::for($post->featured_image),
            ))->article($post->published_at?->toIso8601String()),
            'post' => PostResource::make($post)->withBody()->resolve($request),
            'more' => PostResource::collection($more)->resolve($request),
        ]);
    }
}
