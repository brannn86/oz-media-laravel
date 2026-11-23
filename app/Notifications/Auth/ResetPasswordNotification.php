<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * The password reset email, sent from the `mail` queue so a slow or down
 * SMTP server never stalls (or fails) the "forgot password" request.
 *
 * The station `return` path is captured when the reset is requested: the
 * queue worker building the email has no request to read it from.
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(string $token, public ?string $returnPath = null)
    {
        parent::__construct($token);

        $this->onQueue(config('oz.queues.mail'));
    }

    protected function resetUrl($notifiable): string
    {
        // A reset requested from a station page links back with ?return=, so after
        // resetting and logging in the listener lands on that station again.
        return route('password.reset', array_filter([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'return' => $this->returnPath,
        ]));
    }
}
