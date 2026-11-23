<?php

use App\Actions\NowPlaying\SyncNowPlayingAction;
use App\Enums\SongRequestStatus;
use App\Events\Live\SongRequestUpdated;
use App\Events\NowPlayingUpdated;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\TrackPlay;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const STATUS_URL = 'https://streaming.example.com:8443/status-json.xsl';

beforeEach(function () {
    Http::preventStrayRequests();
    // Later Http::fake() calls don't replace a stub, so one stub serves whatever the test set last.
    $this->icecast = Http::response('not set', 500);
    Http::fake([STATUS_URL => fn () => test()->icecast]);
    Event::fake([NowPlayingUpdated::class, SongRequestUpdated::class]);

    $this->station = Station::factory()->create([
        'slug' => 'oz-bandung',
        'stream_audio_url' => 'https://streaming.example.com:8443/ozbandung',
    ]);
});

/**
 * Icecast's status-json.xsl: one mount is an object, several a list.
 *
 * @param  array<int, array<string, mixed>>  $sources
 */
function icecastReturns(array $sources): void
{
    test()->icecast = Http::response(['icestats' => [
        'server_id' => 'Icecast 2.4.4',
        ...($sources === [] ? [] : ['source' => count($sources) === 1 ? $sources[0] : $sources]),
    ]]);
}

function mount(?string $title, string $path = '/ozbandung', int $listeners = 14): array
{
    return [
        'listenurl' => "http://streaming.example.com:8000{$path}",
        'listeners' => $listeners,
        'bitrate' => 96,
        'title' => $title,
    ];
}

function sync(): ?TrackPlay
{
    return app(SyncNowPlayingAction::class)->handle(test()->station);
}

it('derives the Icecast status URL from the audio stream URL', function (?string $audio, ?array $expected) {
    $station = Station::factory()->make(['stream_audio_url' => $audio]);

    expect($station->icecastStatusEndpoint())->toBe($expected);
})->with([
    'icecast mount' => ['https://streaming.ozradio.id:8443/ozbandung', ['url' => 'https://streaming.ozradio.id:8443/status-json.xsl', 'mount' => '/ozbandung']],
    'no port' => ['http://radio.example.com/live.mp3', ['url' => 'http://radio.example.com/status-json.xsl', 'mount' => '/live.mp3']],
    'hls' => ['https://cdn.example.com/live/index.m3u8', null],
    'no path' => ['https://radio.example.com/', null],
    'empty' => [null, null],
]);

it('records the song on air, parsed as Title - Artist, and broadcasts it', function () {
    icecastReturns([mount('Hatchu!! - Salma Salsabil')]);

    $play = sync();

    expect($play)
        ->title->toBe('Hatchu!!')
        ->artist->toBe('Salma Salsabil')
        ->raw_title->toBe('Hatchu!! - Salma Salsabil')
        ->listeners->toBe(14)
        ->ended_at->toBeNull();

    Event::assertDispatched(NowPlayingUpdated::class, fn (NowPlayingUpdated $event) => $event->track?->is($play)
        && $event->broadcastAs() === 'now-playing.updated'
        && $event->broadcastOn()[0]->name === 'station.oz-bandung'
        && $event->broadcastWith()['track']['title'] === 'Hatchu!!');
});

it('honours the Artist - Title format and titles without an artist', function () {
    $this->station->update(['stream_title_format' => 'artist_title']);
    icecastReturns([mount('Salma Salsabil - Hatchu!!')]);

    expect(sync())->title->toBe('Hatchu!!')->artist->toBe('Salma Salsabil');

    icecastReturns([mount('OZ Radio Station ID')]);

    expect(sync())->title->toBe('OZ Radio Station ID')->artist->toBeNull();
});

it('does nothing while the same song is still playing', function () {
    icecastReturns([mount('Hatchu!! - Salma Salsabil')]);
    $first = sync();

    $again = sync();

    expect($again->is($first))->toBeTrue()
        ->and(TrackPlay::count())->toBe(1);
    Event::assertDispatchedTimes(NowPlayingUpdated::class, 1);
});

it('closes the previous song when the title changes', function () {
    icecastReturns([mount('Hatchu!! - Salma Salsabil')]);
    $first = sync();

    $this->travel(3)->minutes();
    icecastReturns([mount('Komang - Raim Laode')]);
    $second = sync();

    expect($first->fresh()->ended_at)->not->toBeNull()
        ->and($second->title)->toBe('Komang')
        ->and($this->station->trackPlays()->onAir()->count())->toBe(1);
});

it('picks the station mount when the server carries several', function () {
    icecastReturns([mount('Other - Station', '/ozbali'), mount('Komang - Raim Laode')]);

    expect(sync()->title)->toBe('Komang');
});

it('ends the open song when the mount goes offline or loses its title', function (array $sources) {
    icecastReturns([mount('Hatchu!! - Salma Salsabil')]);
    $play = sync();

    icecastReturns($sources);

    expect(sync())->toBeNull()
        ->and($play->fresh()->ended_at)->not->toBeNull();
    Event::assertDispatched(NowPlayingUpdated::class, fn (NowPlayingUpdated $event) => $event->track === null);
})->with([
    'no mounts' => [[]],
    'other mount only' => [[mount('Other - Station', '/ozbali')]],
    'empty title' => [[mount('')]],
]);

it('keeps the song through a short Icecast outage and ends it after the grace period', function () {
    Log::spy();
    icecastReturns([mount('Hatchu!! - Salma Salsabil')]);
    $play = sync();

    $this->icecast = Http::response('Bad gateway', 502);

    expect(sync()?->is($play))->toBeTrue();
    expect(sync()?->is($play))->toBeTrue();
    Log::shouldHaveReceived('warning')->once();

    $this->travel(SyncNowPlayingAction::OUTAGE_GRACE_SECONDS + 1)->seconds();

    expect(sync())->toBeNull()
        ->and($play->fresh()->ended_at)->not->toBeNull();
});

it('marks open requests for the song as played', function () {
    $approved = SongRequest::factory()->approved()->for($this->station)->create(['song_title' => 'hatchu', 'artist' => 'Salma Salsabil']);
    $pending = SongRequest::factory()->for($this->station)->create(['song_title' => 'Hatchu!!', 'artist' => 'salma salsabil']);
    $otherSong = SongRequest::factory()->for($this->station)->create(['song_title' => 'Komang', 'artist' => 'Raim Laode']);
    $otherStation = SongRequest::factory()->create(['song_title' => 'Hatchu!!', 'artist' => 'Salma Salsabil']);

    icecastReturns([mount('Hatchu!! (Radio Edit) - Salma Salsabil feat. Someone')]);
    sync();

    expect($approved->fresh()->status)->toBe(SongRequestStatus::Played)
        ->and($pending->fresh()->status)->toBe(SongRequestStatus::Played)
        ->and($otherSong->fresh()->status)->toBe(SongRequestStatus::Pending)
        ->and($otherStation->fresh()->status)->toBe(SongRequestStatus::Pending);
    Event::assertDispatchedTimes(SongRequestUpdated::class, 2);
});

it('does not match a different artist with the same song title', function () {
    $request = SongRequest::factory()->for($this->station)->create(['song_title' => 'Hatchu!!', 'artist' => 'Somebody Else']);

    icecastReturns([mount('Hatchu!! - Salma Salsabil')]);
    sync();

    expect($request->fresh()->status)->toBe(SongRequestStatus::Pending);
});

it('syncs every active station from the scheduled command', function () {
    Station::factory()->inactive()->create(['stream_audio_url' => 'https://elsewhere.example.com/x']);
    icecastReturns([mount('Hatchu!! - Salma Salsabil')]);

    $this->artisan('stations:sync-now-playing')
        ->expectsOutputToContain('oz-bandung: Hatchu!! / Salma Salsabil')
        ->assertSuccessful();

    Http::assertSentCount(1);
});
