<?php

namespace App\Actions\Schedules;

use App\Enums\DayOfWeek;
use App\Models\Program;
use App\Models\ProgramSchedule;
use Illuminate\Support\Collection;

/**
 * Finds slots on the same station and day that overlap a proposed time range.
 */
class FindScheduleConflicts
{
    /**
     * @return Collection<int, ProgramSchedule>
     */
    public function handle(Program $program, DayOfWeek $day, string $start, string $end, ?int $ignoreScheduleId = null): Collection
    {
        $candidate = new ProgramSchedule([
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        return ProgramSchedule::query()
            ->with('program:id,title')
            ->forStation($program->station_id)
            ->onDay($day)
            ->when($ignoreScheduleId, fn ($query) => $query->whereKeyNot($ignoreScheduleId))
            ->get()
            ->filter(fn (ProgramSchedule $existing): bool => $existing->startMinutes() < $candidate->endMinutes()
                && $candidate->startMinutes() < $existing->endMinutes())
            ->values();
    }
}
