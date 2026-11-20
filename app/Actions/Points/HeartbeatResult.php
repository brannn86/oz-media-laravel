<?php

namespace App\Actions\Points;

final class HeartbeatResult
{
    public function __construct(
        /** False when another heartbeat for this account arrived too recently. */
        public readonly bool $accepted,
        public readonly int $listeningPoints,
        public readonly int $streakBonus,
        public readonly int $streakDays,
        public readonly int $balance,
        public readonly bool $dailyCapReached,
        /** Seconds until the next heartbeat can count. */
        public readonly int $nextInSeconds,
    ) {}

    public function awarded(): int
    {
        return $this->listeningPoints + $this->streakBonus;
    }
}
