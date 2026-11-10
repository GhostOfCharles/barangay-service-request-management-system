<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_add_a_resident(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/residents', [
                'name' => 'Maria Santos',
                'address' => '12 Sampaguita St.',
                'contact_number' => '09171234567',
                'purok' => 'Purok 3',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('residents', ['name' => 'Maria Santos', 'purok' => 'Purok 3']);
    }

    public function test_required_resident_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/residents', ['name' => '', 'address' => '', 'purok' => ''])
            ->assertSessionHasErrorsIn('createResident', ['name', 'address', 'purok']);

        $this->assertDatabaseCount('residents', 0);
    }

    public function test_residents_can_be_searched_by_name_or_contact(): void
    {
        Resident::factory()->create(['name' => 'Juan dela Cruz', 'contact_number' => '09281234567']);
        Resident::factory()->create(['name' => 'Pedro Villanueva', 'contact_number' => '09471234567']);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/residents?search=Juan')
            ->assertSee('Juan dela Cruz')
            ->assertDontSee('Pedro Villanueva');

        $this->actingAs($user)->get('/residents?search=0947')
            ->assertSee('Pedro Villanueva')
            ->assertDontSee('Juan dela Cruz');
    }

    public function test_staff_can_update_a_resident(): void
    {
        $resident = Resident::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put("/residents/{$resident->id}", [
                'name' => 'Updated Name',
                'address' => 'New Address',
                'contact_number' => '',
                'purok' => 'Purok 2',
            ])
            ->assertRedirect("/residents/{$resident->id}");

        $this->assertSame('Updated Name', $resident->fresh()->name);
        $this->assertNull($resident->fresh()->contact_number);
    }
}
