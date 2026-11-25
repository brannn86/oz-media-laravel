<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\AgeRange;
use App\Enums\Gender;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Support\ReturnPath;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password Null for listeners who only use Google sign-in.
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property AgeRange|null $age_range
 * @property Gender|null $gender
 * @property string|null $location Free text, usually one of ListenerLocations.
 * @property CarbonImmutable|null $chat_muted_until
 * @property int $points_balance OZ Poin; always equals the sum of point_transactions.
 * @property int $current_streak_days Consecutive WIB days with listening.
 * @property CarbonImmutable|null $last_listened_date
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'avatar_url', 'age_range', 'gender', 'location'])]
#[Hidden(['password', 'google_id', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Match the column defaults so a freshly created user has them before a reload.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'points_balance' => 0,
        'current_streak_days' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'chat_muted_until' => 'immutable_datetime',
            'points_balance' => 'integer',
            'current_streak_days' => 'integer',
            'last_listened_date' => 'immutable_date',
            'age_range' => AgeRange::class,
            'gender' => Gender::class,
        ];
    }

    /**
     * @return HasMany<PointTransaction, $this>
     */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * @return HasMany<RewardRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    /**
     * Staff hold an admin role; everyone else is a listener.
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(AdminRole::values());
    }

    public function isChatMuted(): bool
    {
        return $this->chat_muted_until?->isFuture() === true;
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Only users holding one of the admin roles may enter the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(AdminRole::values());
    }

    public function isDev(): bool
    {
        return $this->hasRole(AdminRole::Dev->value);
    }

    /**
     * Queued, and remembers the station page the reset was requested from.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token, ReturnPath::from(request())));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }
}
