<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The sign-up "verify your email" message, sent from the `mail` queue so
 * registering never waits on the SMTP server.
 */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct()
    {
        $this->onQueue(config('oz.queues.mail'));
    }
}
