<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: syncs the three admin roles to the matrix in AdminRole::permissions().
 *
 * Re-run after adding a resource: php artisan db:seed --class=RolesAndPermissionsSeeder
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminRole::allPermissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (AdminRole::cases() as $adminRole) {
            $role = Role::findOrCreate($adminRole->value, 'web');

            $role->syncPermissions($adminRole === AdminRole::Dev
                ? Permission::query()->where('guard_name', 'web')->get()
                : $adminRole->permissions());
        }

        // Strictly three roles.
        Role::query()->whereNotIn('name', AdminRole::values())->delete();

        $this->seedDevUser();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Promotes the existing admin account to `dev`, creating it on a fresh database.
     */
    private function seedDevUser(): void
    {
        $user = User::query()->firstOrCreate(['email' => config('oz.admin.email')], [
            'name' => 'OZ Dev',
            'password' => config('oz.admin.password') ?: (app()->isProduction() ? str()->password(24) : 'password'),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([AdminRole::Dev->value]);
    }
}
