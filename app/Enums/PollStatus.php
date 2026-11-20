<?php

namespace App\Enums;

enum PollStatus: string
{
    case Live = 'live';
    case Closed = 'closed';
}
