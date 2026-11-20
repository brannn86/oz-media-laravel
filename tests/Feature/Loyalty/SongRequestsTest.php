<?php

use App\Actions\SongRequests\ModerateSongRequestAction;
use App\Enums\PointReason;
use App\Enums\SongRequestStatus;
use App\Events\Live\SongRequestUpdated;
use App\Models\PointTransaction;
use App\Models\SongRequest;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Event::fake([SongRequestUpdated::class]);
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
    $this->listener = User::factory()->create();
    $this->url = '/api/v1/stations/oz-bandung/requests';
});

it('lets a listener request a song and broadcasts it to the queue', function () {
    Sanctum::actingAs($this->listener);

    $this->postJson($this->url, [
        'song_title' => 'Tak Segampang Itu',
        'artist' => 'Anggi Marito',
        'shoutout_message' => 'Buat temen-temen kantor, dasar anjing kalian',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.shoutout_message', 'Buat temen-temen kantor, dasar a***** kalian');

    Event::assertDispatched(SongRequestUpdated::class, fn ($event) => $event->broadcastOn()[0]->name === 'station.oz-bandung.requests');
});

it('limits open requests and how often a listener can request', function () {
    Sanctum::actingAs($this->listener);
    $song = ['song_title' => 'Lagu', 'artist' => 'Band'];

    $this->postJson($this->url, $song)->assertCreated();
    $this->postJson($this->url, $song)->assertTooManyRequests();

    $this->travel(301)->seconds();
    $this->postJson($this->url, $song)->assertCreated();

    $this->travel(301)->seconds();
    $this->postJson($this->url, $song)->assertUnprocessable()->assertJsonValidationErrors('song_title');
});

it('blocks muted listeners and guests', function () {
    $this->postJson($this->url, ['song_title' => 'x', 'artist' => 'y'])->assertUnauthorized();

    $this->listener->forceFill(['chat_muted_until' => now()->addHour()])->save();
    Sanctum::actingAs($this->listener);
    $this->postJson($this->url, ['song_title' => 'x', 'artist' => 'y'])->assertForbidden();
});

it('toggles upvotes, never on your own request, and orders the queue', function () {
    $own = SongRequest::factory()->for($this->station)->for($this->listener)->create();
    $popular = SongRequest::factory()->for($this->station)->create();
    $approved = SongRequest::factory()->for($this->station)->approved()->create();
    SongRequest::factory()->for($this->station)->create(['status' => SongRequestStatus::Played]);
    Sanctum::actingAs($this->listener);

    $this->postJson("{$this->url}/{$popular->id}/upvote")->assertJsonPath('upvoted', true)->assertJsonPath('data.upvotes_count', 1);
    $this->postJson("{$this->url}/{$own->id}/upvote")->assertUnprocessable();

    $this->getJson($this->url)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $approved->id)   // coming up first
        ->assertJsonPath('data.1.id', $popular->id)    // then most upvoted
        ->assertJsonPath('my_upvotes', [$popular->id]);

    $this->postJson("{$this->url}/{$popular->id}/upvote")->assertJsonPath('upvoted', false)->assertJsonPath('data.upvotes_count', 0);
});

it('404s a request from another station', function () {
    $elsewhere = SongRequest::factory()->create();
    Sanctum::actingAs($this->listener);

    $this->postJson("{$this->url}/{$elsewhere->id}/upvote")->assertNotFound();
});

it('pays the requester once when approved, then moves it to played', function () {
    $request = SongRequest::factory()->for($this->station)->for($this->listener)->create();
    $moderate = app(ModerateSongRequestAction::class);

    $moderate->approve($request);
    expect(fn () => $moderate->approve($request))->toThrow(ValidationException::class);
    $moderate->markPlayed($request);

    expect($request->refresh()->status)->toBe(SongRequestStatus::Played)
        ->and($this->listener->refresh()->points_balance)->toBe(25)
        ->and(PointTransaction::where('reason', PointReason::SongRequestApproved)->count())->toBe(1);
});

it('rejects without paying, and frees the listener to request again', function () {
    $request = SongRequest::factory()->for($this->station)->for($this->listener)->create();

    app(ModerateSongRequestAction::class)->reject($request);

    expect($this->listener->refresh()->points_balance)->toBe(0);
    Sanctum::actingAs($this->listener);
    $this->postJson("{$this->url}/{$request->id}/upvote")->assertUnprocessable();
});
