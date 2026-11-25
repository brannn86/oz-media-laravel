<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            OzBandungSeeder::class,
            OzBaliSeeder::class,
            RewardsSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->seedLocalRoleUsers();
        }
    }

    /**
     * One account per non-dev role for trying out the panel locally (password: "password").
     */
    private function seedLocalRoleUsers(): void
    {
        $accounts = [
            AdminRole::Editorial->value => ['Editorial Tester', 'editorial@ozmedianet.test'],
            AdminRole::SocialMedia->value => ['Social Media Tester', 'social@ozmedianet.test'],
        ];

        foreach ($accounts as $role => [$name, $email]) {
            User::query()
                ->firstOrCreate(['email' => $email], [
                    'name' => $name,
                    'password' => 'password',
                    'email_verified_at' => now(),
                ])
                ->syncRoles([$role]);
        }
    }
}
