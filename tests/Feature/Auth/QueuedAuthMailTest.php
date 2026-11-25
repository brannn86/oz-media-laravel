<?php

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;

it('queues the reset email on the mail queue', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email, 'return' => '/oz-bandung'])
        ->assertSessionHasNoErrors();

    Queue::assertPushedOn('mail', SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof ResetPasswordNotification);
});

it('keeps the station return path when the worker builds the reset email', function () {
    Queue::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email, 'return' => '/oz-bandung/news']);

    /** @var SendQueuedNotifications $job */
    $job = Queue::pushed(SendQueuedNotifications::class)->first();

    // What the queue worker does: rebuild the notification, with no `return` in its request.
    $notification = unserialize(serialize($job->notification));
    $this->app->instance('request', Request::create('/'));

    expect($notification->toMail($user)->actionUrl)
        ->toContain('/reset-password/')
        ->toContain('return='.urlencode('/oz-bandung/news'));
});

it('queues the verification email on sign-up', function () {
    Queue::fake();

    $this->post(route('register.store'), [
        'name' => 'New Listener',
        'email' => 'listener@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'age_range' => '25_34',
        'gender' => 'female',
        'location' => 'Kota Bandung, Jawa Barat',
    ]);

    Queue::assertPushedOn('mail', SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof VerifyEmailNotification);
});

it('sends a test email with oz:mail-test', function () {
    $this->artisan('oz:mail-test', ['to' => 'ops@example.com'])
        ->expectsOutputToContain('nothing left this machine')
        ->assertSuccessful();

    $sent = app('mailer')->getSymfonyTransport()->messages();

    expect($sent)->toHaveCount(1)
        ->and($sent->first()->getOriginalMessage()->getTo()[0]->getAddress())->toBe('ops@example.com');
});

it('rejects a bad address in oz:mail-test', function () {
    $this->artisan('oz:mail-test', ['to' => 'not-an-email'])->assertExitCode(2);
});

it('reports an SMTP failure from oz:mail-test', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 2,
    ]);

    $this->artisan('oz:mail-test', ['to' => 'ops@example.com'])
        ->expectsOutputToContain('Sending failed')
        ->assertFailed();
});
