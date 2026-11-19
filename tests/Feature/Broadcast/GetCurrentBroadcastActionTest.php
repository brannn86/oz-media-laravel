<?php

use App\Actions\Broadcast\GetCurrentBroadcastAction;
use App\Enums\DayOfWeek;
use App\Models\Announcer;
use App\Models\Program;
use App\Models\ProgramSchedule;
use App\Models\Station;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->station = Station::factory()->create();

    $this->breakfast = Program::factory()->for($this->station)->create(['title' => 'Breakfast Club']);
    $this->drive = Program::factory()->for($this->station)->create(['title' => 'Drive Time']);
    $this->late = Program::factory()->for($this->station)->create(['title' => 'Late Night']);

    ProgramSchedule::factory()->for($this->breakfast)->at(DayOfWeek::Monday, '06:00', '10:00')->create();
    ProgramSchedule::factory()->for($this->drive)->at(DayOfWeek::Monday, '16:00', '19:00')->create();
    ProgramSchedule::factory()->for($this->late)->at(DayOfWeek::Sunday, '22:00', '00:00')->create();

    $this->action = app(GetCurrentBroadcastAction::class);
});

// 2026-11-16 is a Monday, 2026-11-22 a Sunday.
function wib(string $datetime): Carbon
{
    return Carbon::parse($datetime, 'Asia/Jakarta');
}

it('returns the program on air with its hosts and what comes next', function () {
    $host = Announcer::factory()->for($this->station)->create(['stage_name' => 'Raka']);
    $this->breakfast->announcers()->attach($host);

    $status = $this->action->handle($this->station, wib('2026-11-16 07:30'));

    expect($status->current->program->title)->toBe('Breakfast Club')
        ->and($status->current->program->announcers->pluck('stage_name')->all())->toBe(['Raka'])
        ->and($status->currentStartsAt->format('Y-m-d H:i'))->toBe('2026-11-16 06:00')
        ->and($status->currentEndsAt->format('Y-m-d H:i'))->toBe('2026-11-16 10:00')
        ->and($status->next->program->title)->toBe('Drive Time')
        ->and($status->nextStartsAt->format('Y-m-d H:i'))->toBe('2026-11-16 16:00');
});

it('treats the end time as exclusive', function () {
    $status = $this->action->handle($this->station, wib('2026-11-16 10:00'));

    expect($status->current)->toBeNull()
        ->and($status->next->program->title)->toBe('Drive Time');
});

it('handles gaps between shows', function () {
    $status = $this->action->handle($this->station, wib('2026-11-16 12:00'));

    expect($status->isOnAir())->toBeFalse()
        ->and($status->refreshAt()->format('H:i'))->toBe('16:00');
});

it('runs a 00:00 end time until midnight and wraps "up next" into the next week', function () {
    $status = $this->action->handle($this->station, wib('2026-11-22 23:59'));

    expect($status->current->program->title)->toBe('Late Night')
        ->and($status->currentEndsAt->format('Y-m-d H:i'))->toBe('2026-11-23 00:00')
        ->and($status->next->program->title)->toBe('Breakfast Club')
        ->and($status->nextStartsAt->format('Y-m-d H:i'))->toBe('2026-11-23 06:00');
});

it('evaluates the moment in WIB whatever timezone it is given', function () {
    // 00:30 UTC on Monday = 07:30 WIB.
    $status = $this->action->handle($this->station, Carbon::parse('2026-11-16 00:30', 'UTC'));

    expect($status->current->program->title)->toBe('Breakfast Club');
});

it('ignores inactive programs, inactive hosts and other stations', function () {
    $this->breakfast->update(['is_active' => false]);
    $this->drive->announcers()->attach(Announcer::factory()->for($this->station)->inactive()->create());

    $other = Program::factory()->create();
    ProgramSchedule::factory()->for($other)->at(DayOfWeek::Monday, '07:00', '08:00')->create();

    $status = $this->action->handle($this->station, wib('2026-11-16 07:30'));
    $later = $this->action->handle($this->station, wib('2026-11-16 17:00'));

    expect($status->current)->toBeNull()
        ->and($later->current->program->announcers)->toBeEmpty();
});

it('returns nothing for a station without a schedule', function () {
    $status = $this->action->handle(Station::factory()->create(), wib('2026-11-16 07:30'));

    expect($status->current)->toBeNull()
        ->and($status->next)->toBeNull()
        ->and($status->refreshAt())->toBeNull();
});
