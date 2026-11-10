<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Creates the first Administrator account. Its temporary password is "password"
     * and must be changed on first login. Demo records are added only in the local environment.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'role' => UserRole::Admin,
                'position' => 'Administrator',
                'is_active' => true,
                'must_change_password' => true,
                'password' => 'password',
            ],
        );

        if (app()->isLocal()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
