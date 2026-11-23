<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Checks the mail setup on a server: sends one plain email right now (not
 * queued) and shows the SMTP error if the server refuses it.
 */
class SendTestMail extends Command
{
    protected $signature = 'oz:mail-test {to : Address to send the test email to}';

    protected $description = 'Send a test email with the current mail settings';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error("\"{$to}\" is not an email address.");

            return self::INVALID;
        }

        $mailer = (string) config('mail.default');

        $this->components->twoColumnDetail('Mailer', $mailer);
        if (config("mail.mailers.{$mailer}.host")) {
            $this->components->twoColumnDetail('Host', config("mail.mailers.{$mailer}.host").':'.config("mail.mailers.{$mailer}.port"));
        }
        $this->components->twoColumnDetail('From', config('mail.from.name').' <'.config('mail.from.address').'>');

        try {
            Mail::raw(
                'This is a test email from '.config('app.name').' ('.config('app.url').'). If you can read it, mail is working.',
                fn (Message $message) => $message->to($to)->subject('Mail test from '.config('app.name')),
            );
        } catch (Throwable $e) {
            $this->components->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->components->warn("Mailer is \"{$mailer}\": nothing left this machine. Set MAIL_MAILER=smtp to send for real.");

            return self::SUCCESS;
        }

        $this->components->info("Sent to {$to}. Check the inbox (and the spam folder).");

        return self::SUCCESS;
    }
}
