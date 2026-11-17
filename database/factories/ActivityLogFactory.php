<?php

namespace Database\Factories;

use App\Enums\ActivityAction;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state: a Staff note on a request.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_name' => fn (array $attributes) => User::find($attributes['user_id'])?->name ?? fake()->name(),
            'user_role' => UserRole::Staff,
            'action' => ActivityAction::RequestNoteAdded,
            'subject_label' => 'SR-'.now()->year.'-'.fake()->numerify('0###'),
            'description' => 'Added a follow-up note.',
            'ip_address' => fake()->localIpv4(),
        ];
    }
}
