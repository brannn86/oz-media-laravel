<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\RecordPostRead;
use App\Models\Station;
use App\Support\ViewerKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * How far into an article a reader got (percent of the text) and for how
 * long they actively read, sent once when they leave it. One read per
 * visitor per article every 30 minutes, like views.
 */
class PostReadController extends Controller
{
    public function __invoke(Request $request, Station $station, string $slug): Response
    {
        $data = $request->validate([
            'depth' => ['required', 'integer', 'between:0,100'],
            'seconds' => ['required', 'integer', 'between:0,86400'],
        ]);

        $post = $station->posts()->published()->where('slug', $slug)->firstOrFail();
        $visitor = hash('xxh128', ViewerKey::from($request));

        if (Cache::add("post-read:{$post->id}:{$visitor}", true, 1800)) {
            RecordPostRead::dispatch(
                $post->id,
                now('Asia/Jakarta')->toDateString(),
                (int) $data['depth'],
                // A tab left open for hours isn't hours of reading.
                min((int) $data['seconds'], 3600),
            );
        }

        return response()->noContent();
    }
}
