<?php

use App\Enums\DayOfWeek;
use App\Models\Announcer;
use App\Models\Program;
use Database\Seeders\OzBandungRosterSeeder;
use Database\Seeders\OzBandungSeeder;

it('seeds the real Bandung roster with its weekly schedule', function () {
    $this->seed(OzBandungSeeder::class);

    expect(Program::count())->toBe(16)
        ->and(Announcer::count())->toBe(18);

    $rilis = Program::where('slug', 'rilis')->first();
    expect($rilis->schedules->map(fn ($slot) => $slot->day_of_week)->all())->toBe(DayOfWeek::weekdays())
        ->and($rilis->announcers->pluck('stage_name')->sort()->values()->all())->toBe(['AJ', 'Alma', 'Aretha']);

    // No real name: the stage name stands in for both.
    expect(Announcer::where('slug', 'kiming')->value('name'))->toBe('Kiming');
});

it('leaves admin edits alone when re-run', function () {
    $this->seed(OzBandungSeeder::class);

    Program::where('slug', 'rilis')->first()->update(['title' => 'RILIS Edited']);
    Program::where('slug', 'rilis')->first()->schedules()->delete();

    $this->seed(OzBandungRosterSeeder::class);

    $rilis = Program::where('slug', 'rilis')->first();
    expect(Program::count())->toBe(16)
        ->and(Announcer::count())->toBe(18)
        ->and($rilis->title)->toBe('RILIS Edited')
        ->and($rilis->schedules()->count())->toBe(0);
});
