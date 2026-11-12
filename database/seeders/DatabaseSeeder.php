<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Creates the first Administrator account (temporary password "password", changed at first login)
     * and the starting service types. Demo records are added only in the local environment.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'role' => UserRole::Administrator,
                'status' => AccountStatus::Active,
                'position' => 'Administrator',
                'must_change_password' => true,
                'password' => 'password',
            ],
        );

        $this->call(ServiceTypeSeeder::class);

        if (app()->isLocal()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
