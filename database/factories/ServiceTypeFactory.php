<?php

namespace Database\Factories;

use App\Enums\RequestCategory;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Certificate',
            'category' => RequestCategory::CertificateClearance,
            'fee' => '50.00',
            'is_active' => true,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => ['fee' => '0.00']);
    }

    /**
     * Add required documents to the checklist.
     *
     * @param  list<string>  $documentNames
     */
    public function withDocuments(array $documentNames = ['Valid ID (front)']): static
    {
        return $this->afterCreating(function (ServiceType $serviceType) use ($documentNames) {
            foreach ($documentNames as $name) {
                $serviceType->documentRequirements()->create(['document_name' => $name, 'is_required' => true]);
            }
        });
    }
}
