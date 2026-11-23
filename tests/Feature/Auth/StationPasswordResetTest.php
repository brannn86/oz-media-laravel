<?php

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('sends a reset link from the station popup that leads back to the station', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->from('/oz-bandung/news')
        ->post(route('password.email'), ['email' => $user->email, 'return' => '/oz-bandung/news'])
        ->assertRedirect('/oz-bandung/news')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        expect($url)
            ->toContain('/reset-password/'.$notification->token)
            ->toContain('email='.urlencode($user->email))
            ->toContain('return='.urlencode('/oz-bandung/news'));

        return true;
    });
});

it('drops unsafe return paths from the reset link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email, 'return' => '//evil.example']);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        expect($notification->toMail($user)->actionUrl)->not->toContain('return=');

        return true;
    });
});

it('gives unknown emails the same answer as real ones', function () {
    Notification::fake();

    $this->from('/oz-bandung')
        ->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect('/oz-bandung')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', trans('passwords.sent'));

    $this->postJson(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJson(['message' => trans('passwords.sent')]);

    Notification::assertNothingSent();
});

it('still reports a throttled second request', function () {
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors('email');
});

it('lands back on the station after resetting and logging in', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email, 'return' => '/oz-bandung/events']);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $this->get($notification->toMail($user)->actionUrl)->assertOk();

        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password-123'])
            ->assertRedirect('/oz-bandung/events');

        return true;
    });
});
