<?php

use App\Actions\Analytics\ProgramAudience;
use App\Enums\DayOfWeek;
use App\Models\Announcer;
use App\Models\Program;
use App\Models\ProgramSchedule;
use App\Models\Station;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

it('credits Icecast samples and website listening to the show on air', function () {
    // Monday 23 Nov 2026, 13:00 WIB.
    $this->travelTo(Carbon::parse('2026-11-23 13:00', 'Asia/Jakarta'));

    $station = Station::factory()->create(['name' => 'OZ Bandung']);
    $host = Announcer::factory()->for($station)->create(['name' => 'Raka', 'stage_name' => null, 'is_active' => true]);
    $show = Program::factory()->for($station)->create(['title' => 'Midday Mix', 'is_active' => true]);
    $show->announcers()->attach($host);
    ProgramSchedule::factory()->for($show)->at(DayOfWeek::Monday, '10:00', '12:00')->create();

    $at = fn (string $time): Carbon => Carbon::parse("2026-11-23 {$time}", 'Asia/Jakarta')->setTimezone(config('app.timezone'));

    DB::table('icecast_samples')->insert([
        ['station_id' => $station->id, 'sampled_at' => $at('10:00'), 'listeners' => 10],
        ['station_id' => $station->id, 'sampled_at' => $at('11:45'), 'listeners' => 20],
        ['station_id' => $station->id, 'sampled_at' => $at('12:15'), 'listeners' => 99], // After the show.
    ]);
    DB::table('audience_minutes')->insert([
        ['station_id' => $station->id, 'minute_at' => $at('10:30'), 'mode' => 'audio', 'listeners' => 3],
        ['station_id' => $station->id, 'minute_at' => $at('10:31'), 'mode' => 'audio', 'listeners' => 3],
        ['station_id' => $station->id, 'minute_at' => $at('10:32'), 'mode' => 'video', 'listeners' => 5], // Video doesn't count.
    ]);

    $row = collect(app(ProgramAudience::class)->programs(1))->firstWhere('program', 'Midday Mix');

    expect($row)
        ->airtime_hours->toBe(2.0)
        ->avg_icecast->toBe(15.0)
        ->peak_icecast->toBe(20)
        ->avg_website->toBe(round(6 / 120, 2))
        ->hosts->toBe('Raka');

    $hostRow = collect(app(ProgramAudience::class)->hosts(1))->firstWhere('host', 'Raka');

    expect($hostRow)->shows->toBe('Midday Mix')->avg_icecast->toBe(15.0);
});
