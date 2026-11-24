<?php

namespace App\Support\Icecast;

use Carbon\CarbonImmutable;

/**
 * One mount from Icecast's status-json.xsl.
 */
final class IcecastSource
{
    public function __construct(
        public readonly ?string $title,
        public readonly int $listeners,
        public readonly ?int $bitrate,
        /** Most listeners at once since the stream (re)started. */
        public readonly ?int $listenerPeak = null,
        public readonly ?CarbonImmutable $streamStartedAt = null,
    ) {}
}
