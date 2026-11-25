<?php

use App\Actions\Analytics\RequestMetrics;
use App\Enums\SongRequestStatus;
use App\Models\SongRequest;
use App\Models\Station;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-25 20:00', 'Asia/Jakarta'));
    $this->station = Station::factory()->create();
});

function requested(string $song, SongRequestStatus $status, ?int $playedAfterMinutes = null, int $upvotes = 0): SongRequest
{
    return SongRequest::factory()->for(test()->station)->create([
        'song_title' => $song,
        'artist' => 'Salma Salsabil',
        'status' => $status,
        'upvotes_count' => $upvotes,
        'created_at' => now()->subHours(3),
        'decided_at' => $playedAfterMinutes !== null ? now()->subHours(3)->addMinutes($playedAfterMinutes) : null,
    ]);
}

it('measures how many requests reach the air and how long they wait', function () {
    requested('Hatchu!!', SongRequestStatus::Played, 10);
    requested('Hatchu!!', SongRequestStatus::Played, 30);
    requested('Hatchu!!', SongRequestStatus::Played, 50);
    requested('Rumah', SongRequestStatus::Rejected, 5);
    requested('Rumah', SongRequestStatus::Pending);

    expect(app(RequestMetrics::class)->summary(1))->toBe([
        'submitted' => 5,
        'played' => 3,
        'rejected' => 1,
        'waiting' => 1,
        'play_rate' => 75.0,
        'median_wait_minutes' => 30.0,
        'average_wait_minutes' => 30.0,
    ]);
});

it('counts the same song together however it was typed', function () {
    requested('Hatchu!!', SongRequestStatus::Played, 10, upvotes: 4);
    requested('hatchu', SongRequestStatus::Pending, upvotes: 1);
    requested('Rumah', SongRequestStatus::Pending);

    $songs = app(RequestMetrics::class)->mostRequested(1);

    expect($songs[0])->toMatchArray(['requests' => 2, 'people' => 2, 'upvotes' => 5, 'played' => 1])
        ->and($songs)->toHaveCount(2);
});
