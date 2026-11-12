<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_no' => 'SR-'.now()->year.'-'.fake()->unique()->numerify('9###'),
            'resident_id' => Resident::factory(),
            'service_type_id' => ServiceType::factory(),
            'purpose' => fake()->sentence(),
            'status' => RequestStatus::Submitted,
            'fee_amount' => '50.00',
            'received_at' => today(),
            'created_by' => User::factory(),
        ];
    }

    public function status(RequestStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'completed_at' => $status === RequestStatus::Completed ? now() : null,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => ['fee_amount' => '0.00']);
    }
}
