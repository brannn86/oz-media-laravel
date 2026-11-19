<?php

namespace App\Rules;

use App\Actions\Schedules\FindScheduleConflicts;
use App\Enums\DayOfWeek;
use App\Models\Program;
use App\Models\ProgramSchedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a schedule's end time: it must come after the start time
 * (00:00 = midnight) and must not overlap another slot on the station.
 */
class ScheduleSlotIsFree implements ValidationRule
{
    public function __construct(
        private readonly ?int $programId,
        private readonly DayOfWeek|int|string|null $day,
        private readonly ?string $start,
        private readonly ?int $ignoreScheduleId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || blank($this->start) || $this->day === null || $this->day === '') {
            return;
        }

        $end = (string) $value;
        $endMinutes = ProgramSchedule::toMinutes($end) ?: 24 * 60;

        if ($endMinutes <= ProgramSchedule::toMinutes($this->start)) {
            $fail('The end time must be after the start time (use 00:00 for midnight).');

            return;
        }

        $program = $this->programId ? Program::find($this->programId) : null;

        if (! $program) {
            return;
        }

        $day = $this->day instanceof DayOfWeek ? $this->day : DayOfWeek::from((int) $this->day);

        $conflicts = app(FindScheduleConflicts::class)
            ->handle($program, $day, $this->start, $end, $this->ignoreScheduleId);

        if ($conflicts->isNotEmpty()) {
            $clash = $conflicts->first();

            $fail("Overlaps with \"{$clash->program->title}\" ({$clash->timeRangeLabel()}) on {$day->getLabel()}.");
        }
    }
}
