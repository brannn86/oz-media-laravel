<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ProgramScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A weekly time slot in station local time (WIB for OZ Bandung).
 *
 * An end_time of 00:00 means the slot runs until midnight.
 *
 * @property int $id
 * @property int $program_id
 * @property DayOfWeek $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['program_id', 'day_of_week', 'start_time', 'end_time'])]
class ProgramSchedule extends Model
{
    /** @use HasFactory<ProgramScheduleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
        ];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeForStation(Builder $query, Station|int $station): void
    {
        $query->whereHas('program', fn (Builder $program) => $program->where(
            'station_id',
            $station instanceof Station ? $station->getKey() : $station,
        ));
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOnDay(Builder $query, DayOfWeek $day): void
    {
        $query->where('day_of_week', $day->value);
    }

    public function startMinutes(): int
    {
        return self::toMinutes($this->start_time);
    }

    public function endMinutes(): int
    {
        $minutes = self::toMinutes($this->end_time);

        return $minutes === 0 ? 24 * 60 : $minutes;
    }

    public function isOnAirAt(CarbonInterface $moment): bool
    {
        if ($moment->dayOfWeek !== $this->day_of_week->value) {
            return false;
        }

        $minutes = $moment->hour * 60 + $moment->minute;

        return $minutes >= $this->startMinutes() && $minutes < $this->endMinutes();
    }

    public function timeRangeLabel(): string
    {
        return substr($this->start_time, 0, 5).' – '.substr($this->end_time, 0, 5).' WIB';
    }

    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time) + [0, 0]);

        return $hours * 60 + $minutes;
    }
}
