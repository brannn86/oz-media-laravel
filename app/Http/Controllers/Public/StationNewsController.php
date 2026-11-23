<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Jobs\RecordPostView;
use App\Models\Post;
use App\Models\Station;
use App\Support\PageMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $this->countView($request, $post);

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
                image: $post->webpImageUrl('featured'),
            ))->article($post->published_at?->toIso8601String()),
            'post' => PostResource::make($post)->withBody()->resolve($request),
            'more' => PostResource::collection($more)->resolve($request),
        ]);
    }

    /**
     * One view per visitor per article every 30 minutes; counted on the analytics queue.
     */
    private function countView(Request $request, Post $post): void
    {
        $visitor = $request->hasSession() ? $request->session()->getId() : (string) $request->ip();

        if (Cache::add("post-view:{$post->id}:".hash('xxh128', $visitor), true, 1800)) {
            RecordPostView::dispatch($post->id, now('Asia/Jakarta')->toDateString());
        }
    }
}
