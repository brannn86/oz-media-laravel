<?php

use App\Actions\Stations\SetStreamMode;
use App\Events\StationStreamStatusUpdated;
use App\Models\Station;
use App\Support\VideoStream;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Event;

it('broadcasts when the live mode or a stream URL changes', function (array $changes) {
    Event::fake([StationStreamStatusUpdated::class]);
    $station = Station::factory()->create(['stream_video_url' => 'https://www.youtube.com/watch?v=abcdefghijk']);

    $station->update($changes);

    Event::assertDispatched(StationStreamStatusUpdated::class, fn ($event) => $event->station->is($station));
})->with([
    'video live' => [['is_video_live' => true]],
    'audio url' => [['stream_audio_url' => 'https://new.example.com/live.mp3']],
    'video url' => [['stream_video_url' => 'https://example.com/live.m3u8']],
]);

it('does not broadcast for unrelated edits', function () {
    Event::fake([StationStreamStatusUpdated::class]);
    $station = Station::factory()->create();

    $station->update(['tagline' => 'Something new', 'about_content' => '<p>Hi</p>']);

    Event::assertNotDispatched(StationStreamStatusUpdated::class);
});

it('broadcasts the Filament toggle on the station channel with the player payload', function () {
    Event::fake([StationStreamStatusUpdated::class]);
    $station = Station::factory()->create([
        'slug' => 'oz-bandung',
        'stream_audio_url' => 'https://stream.example.com/oz.mp3',
        'stream_video_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
    ]);

    app(SetStreamMode::class)->toggle($station);

    Event::assertDispatched(StationStreamStatusUpdated::class, function (StationStreamStatusUpdated $event): bool {
        $channel = $event->broadcastOn()[0];

        return $channel instanceof Channel
            && $channel->name === 'station.oz-bandung'
            && $event->broadcastAs() === 'stream-status.updated'
            && $event->broadcastWith() === [
                'station' => 'oz-bandung',
                'audio_url' => 'https://stream.example.com/oz.mp3',
                'video' => ['type' => 'youtube', 'src' => 'https://www.youtube.com/embed/abcdefghijk?autoplay=1'],
                'is_video_live' => true,
            ];
    });
});

it('works out how to play a video stream URL', function (?string $url, ?array $expected) {
    expect(VideoStream::describe($url))->toBe($expected);
})->with([
    'watch' => ['https://www.youtube.com/watch?v=abcdefghijk', ['type' => 'youtube', 'src' => 'https://www.youtube.com/embed/abcdefghijk?autoplay=1']],
    'youtu.be' => ['https://youtu.be/abcdefghijk', ['type' => 'youtube', 'src' => 'https://www.youtube.com/embed/abcdefghijk?autoplay=1']],
    'live' => ['https://www.youtube.com/live/abcdefghijk', ['type' => 'youtube', 'src' => 'https://www.youtube.com/embed/abcdefghijk?autoplay=1']],
    'channel live' => ['https://www.youtube.com/embed/live_stream?channel=UC123', ['type' => 'youtube', 'src' => 'https://www.youtube.com/embed/live_stream?channel=UC123&autoplay=1']],
    'hls' => ['https://cdn.example.com/live/index.m3u8?token=1', ['type' => 'hls', 'src' => 'https://cdn.example.com/live/index.m3u8?token=1']],
    'other embed' => ['https://player.example.com/live', ['type' => 'embed', 'src' => 'https://player.example.com/live']],
    'empty' => [null, null],
]);
