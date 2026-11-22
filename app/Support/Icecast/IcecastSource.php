<?php

namespace App\Support\Icecast;

/**
 * One mount from Icecast's status-json.xsl.
 */
final class IcecastSource
{
    public function __construct(
        public readonly ?string $title,
        public readonly int $listeners,
        public readonly ?int $bitrate,
    ) {}
}
