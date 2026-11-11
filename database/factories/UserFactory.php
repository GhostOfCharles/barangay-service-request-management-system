<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state: an active Staff account.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => 'user'.fake()->unique()->numberBetween(1000, 99999),
            'role' => UserRole::Staff,
            'status' => AccountStatus::Active,
            'position' => 'Front Desk',
            'must_change_password' => false,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Administrator,
            'position' => 'Administrator',
        ]);
    }

    public function resident(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Resident,
            'position' => null,
        ]);
    }

    public function status(AccountStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'must_change_password' => true,
        ]);
    }
}
