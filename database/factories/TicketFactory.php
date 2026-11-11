<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Resident;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_no' => 'TK-'.now()->year.'-'.fake()->unique()->numerify('9###'),
            'resident_id' => Resident::factory()->withAccount(),
            'subject' => fake()->sentence(3),
            'status' => TicketStatus::Open,
        ];
    }
}
