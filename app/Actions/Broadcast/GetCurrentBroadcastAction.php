<?php

namespace App\Actions\Broadcast;

use App\Models\ProgramSchedule;
use App\Models\Station;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resolves the program on air now (and up next) from the weekly schedule.
 *
 * Schedules are stored in station local time, so the moment is evaluated in
 * Asia/Jakarta (WIB) regardless of the app or server timezone.
 */
class GetCurrentBroadcastAction
{
    public const TIMEZONE = 'Asia/Jakarta';

    private const MINUTES_PER_DAY = 1440;

    private const MINUTES_PER_WEEK = 10080;

    public function handle(Station $station, ?CarbonInterface $at = null): BroadcastStatus
    {
        $now = CarbonImmutable::instance($at ?? now())->setTimezone(self::TIMEZONE)->startOfMinute();
        $nowOfWeek = $now->dayOfWeek * self::MINUTES_PER_DAY + $now->hour * 60 + $now->minute;

        $slots = $this->slots($station);

        $current = $slots->first(fn (ProgramSchedule $slot): bool => $slot->isOnAirAt($now));

        // The soonest slot to start after now, wrapping around the end of the week.
        $next = $slots
            ->reject(fn (ProgramSchedule $slot): bool => $slot->is($current))
            ->map(fn (ProgramSchedule $slot): array => [
                'slot' => $slot,
                'in' => $this->minutesUntil($this->startOfWeekMinutes($slot), $nowOfWeek),
            ])
            ->sortBy('in')
            ->first();

        $startOfToday = $now->startOfDay();

        return new BroadcastStatus(
            at: $now,
            current: $current,
            currentStartsAt: $current ? $startOfToday->addMinutes($current->startMinutes()) : null,
            currentEndsAt: $current ? $startOfToday->addMinutes($current->endMinutes()) : null,
            next: $next['slot'] ?? null,
            nextStartsAt: $next ? $now->addMinutes($next['in']) : null,
        );
    }

    /**
     * @return Collection<int, ProgramSchedule>
     */
    private function slots(Station $station): Collection
    {
        return ProgramSchedule::query()
            ->whereHas('program', fn (Builder $query) => $query
                ->where('station_id', $station->getKey())
                ->where('is_active', true))
            ->with(['program.announcers' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    private function startOfWeekMinutes(ProgramSchedule $slot): int
    {
        return $slot->day_of_week->value * self::MINUTES_PER_DAY + $slot->startMinutes();
    }

    private function minutesUntil(int $target, int $from): int
    {
        $delta = ($target - $from + self::MINUTES_PER_WEEK) % self::MINUTES_PER_WEEK;

        // A slot starting this very minute is "now", not a week away; a 0 here only
        // happens when that slot is current, which is rejected above.
        return $delta === 0 ? self::MINUTES_PER_WEEK : $delta;
    }
}
