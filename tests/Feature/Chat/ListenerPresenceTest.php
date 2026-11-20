<?php

use App\Models\Station;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Presence signing is local HMAC work: no Reverb server needed.
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => '1',
    ]);
    Station::factory()->create(['slug' => 'oz-bandung']);
    $this->url = '/api/v1/stations/oz-bandung/listeners/auth';
});

it('lets guests join the listener presence channel without exposing who they are', function () {
    $response = $this->postJson($this->url, [
        'socket_id' => '123.456',
        'channel_name' => 'presence-station.oz-bandung.listeners',
    ], ['X-Listener-Id' => 'device-abc'])->assertOk();

    $channelData = json_decode($response->json('channel_data'), true);

    expect($response->json('auth'))->toStartWith('test-key:')
        ->and($channelData['user_id'])->toBe('guest:'.hash('sha256', 'device-abc'))
        ->and($channelData['user_info'])->toBe(['listener' => true]);
});

it('identifies signed-in listeners by account', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson($this->url, [
        'socket_id' => '123.456',
        'channel_name' => 'presence-station.oz-bandung.listeners',
    ])->assertOk();

    expect(json_decode($response->json('channel_data'), true)['user_id'])->toBe("user:{$user->id}");
});

it('refuses to sign any other channel', function () {
    $this->postJson($this->url, [
        'socket_id' => '123.456',
        'channel_name' => 'presence-station.oz-bali.listeners',
    ])->assertForbidden();

    $this->postJson($this->url, [
        'socket_id' => 'not-a-socket',
        'channel_name' => 'presence-station.oz-bandung.listeners',
    ])->assertUnprocessable();
});
