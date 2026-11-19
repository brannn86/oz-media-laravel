<?php

namespace App\Actions\Broadcast;

use App\Models\ProgramSchedule;
use Carbon\CarbonImmutable;

/**
 * What a station is airing at a given moment, and what follows.
 */
final class BroadcastStatus
{
    public function __construct(
        public readonly CarbonImmutable $at,
        public readonly ?ProgramSchedule $current,
        public readonly ?CarbonImmutable $currentStartsAt,
        public readonly ?CarbonImmutable $currentEndsAt,
        public readonly ?ProgramSchedule $next,
        public readonly ?CarbonImmutable $nextStartsAt,
    ) {}

    public function isOnAir(): bool
    {
        return $this->current !== null;
    }

    /**
     * When this status stops being accurate: the end of the current slot or the start of the next.
     */
    public function refreshAt(): ?CarbonImmutable
    {
        return $this->currentEndsAt ?? $this->nextStartsAt;
    }
}
