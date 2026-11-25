<?php

use App\Actions\Analytics\AnalyticsMetrics;
use App\Actions\Analytics\RecordAudience;
use App\Enums\AudienceMode;
use App\Models\Station;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->station = Station::factory()->create(['slug' => 'oz-bandung']);
    $this->travelTo(Carbon::parse('2026-11-24 10:00:05', 'Asia/Jakarta'));
});

function pingAs(string $device, string $mode = 'audio', array $source = [])
{
    return test()->withHeader('X-Listener-Id', $device)
        ->postJson('/api/v1/stations/oz-bandung/audience', ['mode' => $mode, 'source' => $source]);
}

it('counts each player once a minute, guests included', function () {
    pingAs('phone-a')->assertNoContent();
    pingAs('phone-a')->assertNoContent(); // Second tab, same minute: not counted twice.
    pingAs('phone-b')->assertNoContent();
    pingAs('laptop-c', 'video')->assertNoContent();

    $this->travel(1)->minutes();
    expect(app(RecordAudience::class)->flush())->toBe(2);

    expect(DB::table('audience_minutes')->where('mode', 'audio')->value('listeners'))->toBe(2)
        ->and(DB::table('audience_minutes')->where('mode', 'video')->value('listeners'))->toBe(1);
});

it('leaves the current minute alone and can flush the same minute again', function () {
    pingAs('phone-a');

    expect(app(RecordAudience::class)->flush())->toBe(0);

    $this->travel(1)->minutes();
    app(RecordAudience::class)->flush();
    app(RecordAudience::class)->flush();

    expect(DB::table('audience_minutes')->count())->toBe(1)
        ->and(DB::table('audience_minutes')->value('listeners'))->toBe(1);
});

it('records each visitor once a day per mode', function () {
    foreach (range(1, 3) as $minute) {
        pingAs('phone-a');
        $this->travel(1)->minutes();
    }
    pingAs('phone-a', 'video');

    expect(DB::table('audience_visitors')->where('mode', 'audio')->count())->toBe(1)
        ->and(DB::table('audience_visitors')->where('mode', 'video')->count())->toBe(1);
});

it('turns pings into hours, unique people and peak concurrency', function () {
    // Three listeners for two minutes, then one for a minute.
    foreach ([['a', 'b', 'c'], ['a', 'b', 'c'], ['a']] as $devices) {
        foreach ($devices as $device) {
            pingAs($device);
        }
        $this->travel(1)->minutes();
    }
    app(RecordAudience::class)->flush();

    $metrics = app(AnalyticsMetrics::class);

    expect($metrics->uniqueAudience(AudienceMode::Audio, 1))->toBe(3)
        ->and($metrics->peakConcurrent(AudienceMode::Audio, 1))->toBe(3)
        ->and($metrics->audienceHours(AudienceMode::Audio, 1))->toBe(round(7 / 60, 1));
});

it('rejects unknown modes', function () {
    pingAs('phone-a', 'smell')->assertUnprocessable();
});

it('keeps one session per unbroken stretch of listening', function () {
    foreach (range(1, 4) as $minute) {
        pingAs('phone-a');
        $this->travel(1)->minutes();
    }

    // Gone for 5 minutes, then back: a new session.
    $this->travel(5)->minutes();
    pingAs('phone-a');

    $sessions = DB::table('audience_sessions')->orderBy('id')->get();

    expect($sessions)->toHaveCount(2)
        ->and($sessions[0]->minutes)->toBe(4)
        ->and($sessions[1]->minutes)->toBe(1);
});

it('labels a session with where the visit came from', function (array $source, string $expected) {
    pingAs('phone-a', 'audio', $source);

    expect(DB::table('audience_sessions')->value('source'))->toBe($expected);
})->with([
    'direct' => [[], 'Direct'],
    'instagram link' => [['referrer_host' => 'l.instagram.com'], 'Instagram'],
    'google search' => [['referrer_host' => 'www.google.co.id'], 'Google'],
    'other site' => [['referrer_host' => 'www.kompas.com'], 'kompas.com'],
    'utm wins' => [['referrer_host' => 'www.google.com', 'utm_source' => 'tiktok', 'utm_campaign' => 'konser-amal'], 'TikTok'],
]);

it('keeps the campaign of a tagged link', function () {
    pingAs('phone-a', 'audio', ['utm_source' => 'ig', 'utm_medium' => 'story', 'utm_campaign' => 'drive-time']);

    expect((array) DB::table('audience_sessions')->first(['source', 'utm_medium', 'utm_campaign']))
        ->toBe(['source' => 'Instagram', 'utm_medium' => 'story', 'utm_campaign' => 'drive-time']);
});
