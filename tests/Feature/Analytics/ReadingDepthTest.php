<?php

use App\Actions\Analytics\AnalyticsMetrics;
use App\Models\Post;
use App\Models\Station;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
    $this->post = Post::factory()->for($this->station)->published()->create(['title' => 'Konser Amal']);
});

function readAs(string $device, int $depth, int $seconds)
{
    return test()->withHeader('X-Listener-Id', $device)
        ->postJson('/api/v1/stations/oz-bandung/news/'.test()->post->slug.'/read', compact('depth', 'seconds'));
}

it('adds up how far and how long an article is read', function () {
    readAs('a', 100, 120)->assertNoContent();
    readAs('b', 40, 30)->assertNoContent();
    readAs('c', 80, 9000)->assertNoContent(); // A tab left open: capped at an hour.

    $row = DB::table('post_reads')->first();

    expect($row)->reads->toBe(3)
        ->seconds->toBe(120 + 30 + 3600)
        ->reached_25->toBe(3)
        ->reached_50->toBe(2)
        ->reached_75->toBe(2)
        ->finished->toBe(1);

    $summary = app(AnalyticsMetrics::class)->readingSummary(1);

    expect($summary)->toMatchArray(['reads' => 3, 'average_seconds' => 1250, 'finished' => 33.3, 'reached_50' => 66.7]);
    expect(app(AnalyticsMetrics::class)->articleReading(1)[0])->toMatchArray(['title' => 'Konser Amal', 'reads' => 3, 'finished' => 33.3]);
});

it('counts one read per visitor per article every 30 minutes', function () {
    readAs('a', 30, 10);
    readAs('a', 100, 60);

    expect(DB::table('post_reads')->value('reads'))->toBe(1);
});

it('rejects nonsense and unknown articles', function () {
    readAs('a', 140, 10)->assertUnprocessable();
    test()->postJson('/api/v1/stations/oz-bandung/news/no-such-story/read', ['depth' => 50, 'seconds' => 10])->assertNotFound();
});
