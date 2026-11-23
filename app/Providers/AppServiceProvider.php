<?php

namespace App\Providers;

use App\Enums\AdminRole;
use App\Models\Announcer;
use App\Models\Event;
use App\Models\Post;
use App\Models\Program;
use App\Models\ProgramSchedule;
use App\Models\SocialHighlight;
use App\Models\Station;
use App\Models\User;
use App\Observers\StationContentObserver;
use App\Support\ProfanityFilter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Not a singleton: long-running workers must pick up list edits (the list itself is cached).
        $this->app->bind(ProfanityFilter::class, fn (): ProfanityFilter => ProfanityFilter::fromDatabase());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->protectAdminRoles();
        $this->invalidateStationCaches();

        // Laravel Pulse (/pulse): server health, queues, Reverb load. Developers only.
        Gate::define('viewPulse', fn (User $user): bool => $user->isDev());
    }

    /**
     * Anything the public site shows for a station flushes that station's cache.
     */
    protected function invalidateStationCaches(): void
    {
        foreach ([Station::class, Program::class, ProgramSchedule::class, Announcer::class, Post::class, Event::class, SocialHighlight::class] as $model) {
            $model::observe(StationContentObserver::class);
        }
    }

    /**
     * Role names are identifiers used by code (panel access, Shield super admin),
     * so the built-in roles can't be renamed. Display labels live in AdminRole.
     */
    protected function protectAdminRoles(): void
    {
        Role::updating(function (Role $role): void {
            if ($role->isDirty('name') && in_array($role->getOriginal('name'), AdminRole::values(), true)) {
                throw ValidationException::withMessages([
                    'data.name' => 'Built-in roles cannot be renamed. Their display names are set in App\Enums\AdminRole.',
                ]);
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
