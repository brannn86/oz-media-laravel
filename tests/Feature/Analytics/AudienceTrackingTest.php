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

function pingAs(string $device, string $mode = 'audio')
{
    return test()->withHeader('X-Listener-Id', $device)
        ->postJson('/api/v1/stations/oz-bandung/audience', ['mode' => $mode]);
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
