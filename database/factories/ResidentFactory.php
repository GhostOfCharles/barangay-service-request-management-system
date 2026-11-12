<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\VerificationStatus;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resident>
 */
class ResidentFactory extends Factory
{
    /**
     * Define the model's default state: a verified resident record without a login account.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'address' => fake()->buildingNumber().' '.fake()->streetName(),
            'purok' => 'Purok '.fake()->numberBetween(1, 7),
            'contact_number' => '09'.fake()->numerify('#########'),
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ];
    }

    /**
     * A resident with a login account in the matching status.
     */
    public function withAccount(AccountStatus $status = AccountStatus::Active): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->resident()->status($status)->state(['name' => $attributes['full_name']]),
            'privacy_consent_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => VerificationStatus::Pending,
            'verified_at' => null,
            'valid_id_path' => 'valid-ids/sample-id.png',
        ]);
    }
}
