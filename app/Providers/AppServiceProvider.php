<?php

namespace App\Providers;

use App\Enums\AdminRole;
use App\Support\ProfanityFilter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        $this->registerDevCommands();
    }

    /**
     * `composer dev` / `php artisan dev` also runs the scheduler, so the
     * Icecast now-playing sync works locally (Reverb registers itself).
     */
    protected function registerDevCommands(): void
    {
        if ($this->app->runningInConsole() && class_exists(DevCommands::class)) {
            DevCommands::artisan('schedule:work', 'scheduler');
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
