<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
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
            'category' => fake()->randomElement(RequestCategory::cases()),
            'description' => fake()->sentence(),
            'document_status' => DocumentStatus::Complete,
            'date_received' => today(),
            'status' => RequestStatus::Pending,
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
}
