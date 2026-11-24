<?php

namespace App\Http\Controllers\Public;

use App\Enums\PostCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Jobs\RecordPostView;
use App\Models\Post;
use App\Models\Station;
use App\Support\PageMeta;
use App\Support\StationCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StationNewsController extends Controller
{
    /** Days of views behind the "Most read" list. */
    private const MOST_READ_DAYS = 7;

    public function index(Request $request, Station $station): Response
    {
        $category = $request->filled('category')
            ? PostCategory::tryFrom((string) $request->query('category')) ?? abort(404)
            : null;

        $posts = $station->posts()
            ->published()
            ->when($category, fn ($query) => $query->where('category', $category))
            ->with('author')
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $title = $category ? "{$category->getLabel()} | OZ News" : 'OZ News';

        return Inertia::render('station/news/index', [
            'meta' => PageMeta::make("{$title} | {$station->name}", "Latest stories from {$station->name}."),
            'posts' => PostResource::collection($posts),
            'category' => $category?->toArray(),
            'categories' => array_map(fn (PostCategory $case): array => $case->toArray(), PostCategory::cases()),
        ]);
    }

    public function show(Request $request, Station $station, string $slug): Response
    {
        $post = $station->posts()->published()->with('author')->where('slug', $slug)->firstOrFail();

        $this->countView($request, $post);

        return Inertia::render('station/news/show', [
            'meta' => (new PageMeta(
                title: ($post->meta_title ?: $post->title)." | {$station->name}",
                description: $post->meta_description ?: $post->excerpt ?: $post->body,
                image: $post->webpImageUrl('featured'),
            ))->article($post->published_at?->toIso8601String()),
            'post' => PostResource::make($post)->withBody()->resolve($request),
            'more' => PostResource::collection($this->moreLike($station, $post))->resolve($request),
            'mostRead' => StationCache::remember(
                $station,
                'news-most-read',
                fn (): array => PostResource::collection($this->mostRead($station))->resolve(),
                ttl: 900,
            ),
        ]);
    }

    /**
     * "More in [section]": the latest from the same category, topped up with
     * the latest stories overall when the section is thin.
     *
     * @return Collection<int, Post>
     */
    private function moreLike(Station $station, Post $post, int $limit = 4): Collection
    {
        $latest = fn () => $station->posts()->published()->whereKeyNot($post->getKey())->latest('published_at');

        $more = $post->category
            ? $latest()->where('category', $post->category)->limit($limit)->get()
            : $post->newCollection();

        if ($more->count() < $limit) {
            $more = $more->concat(
                $latest()->whereKeyNot($more->modelKeys())->limit($limit - $more->count())->get()
            );
        }

        return $more;
    }

    /**
     * The station's most viewed stories over the last week (WIB days).
     *
     * @return Collection<int, Post>
     */
    private function mostRead(Station $station, int $limit = 5): Collection
    {
        $since = now('Asia/Jakarta')->subDays(self::MOST_READ_DAYS - 1)->toDateString();

        return $station->posts()
            ->published()
            ->joinSub(
                DB::table('post_views')
                    ->select('post_id', DB::raw('SUM(views) as recent_views'))
                    ->where('date', '>=', $since)
                    ->groupBy('post_id'),
                'recent',
                'recent.post_id',
                '=',
                'posts.id',
            )
            ->select('posts.*')
            ->orderByDesc('recent.recent_views')
            ->latest('published_at')
            ->limit($limit)
            ->get();
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
