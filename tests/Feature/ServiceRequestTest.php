<?php

namespace Tests\Feature;

use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Enums\RequestUpdateType;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Resident $resident): array
    {
        return [
            'resident_id' => $resident->id,
            'category' => RequestCategory::Complaint->value,
            'description' => 'Noise complaint against neighbor.',
            'document_status' => 'not_required',
            'date_received' => today()->toDateString(),
        ];
    }

    public function test_staff_can_create_a_request_with_a_unique_reference(): void
    {
        $user = User::factory()->create();
        $resident = Resident::factory()->create();

        $this->actingAs($user)->post('/service-requests', $this->validPayload($resident))->assertRedirect();
        $this->actingAs($user)->post('/service-requests', $this->validPayload($resident))->assertRedirect();

        $year = now()->year;
        $this->assertDatabaseHas('service_requests', ['reference_no' => "SR-{$year}-0001", 'status' => 'pending', 'created_by' => $user->id]);
        $this->assertDatabaseHas('service_requests', ['reference_no' => "SR-{$year}-0002"]);
        $this->assertDatabaseHas('request_updates', ['type' => RequestUpdateType::Created->value]);
    }

    public function test_request_requires_valid_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/service-requests', [
                'resident_id' => 999,
                'category' => 'unknown',
                'description' => '',
                'document_status' => '',
                'date_received' => today()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrorsIn('createRequest', ['resident_id', 'category', 'description', 'document_status', 'date_received']);

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_status_change_with_note_is_one_history_entry(): void
    {
        $user = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create();

        $this->actingAs($user)
            ->post("/service-requests/{$serviceRequest->id}/follow-ups", [
                'action_date' => today()->toDateString(),
                'status' => 'completed',
                'note' => 'Released to the resident.',
            ])
            ->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame(RequestStatus::Completed, $serviceRequest->status);
        $this->assertNotNull($serviceRequest->completed_at);
        $this->assertSame(1, $serviceRequest->updates()->count());
        $this->assertDatabaseHas('request_updates', [
            'service_request_id' => $serviceRequest->id,
            'type' => 'status_change',
            'from_status' => 'pending',
            'to_status' => 'completed',
            'note' => 'Released to the resident.',
            'user_id' => $user->id,
        ]);
    }

    public function test_status_can_change_without_a_note(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/service-requests/{$serviceRequest->id}/follow-ups", ['action_date' => today()->toDateString(), 'status' => 'in_progress'])
            ->assertSessionHasNoErrors();

        $this->assertSame(RequestStatus::InProgress, $serviceRequest->fresh()->status);
    }

    public function test_follow_up_without_status_change_keeps_the_status(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/service-requests/{$serviceRequest->id}/follow-ups", [
                'action_date' => today()->toDateString(),
                'note' => 'Called the resident to submit a valid ID.',
            ])
            ->assertRedirect();

        $this->assertSame(RequestStatus::Pending, $serviceRequest->fresh()->status);
        $this->assertDatabaseHas('request_updates', ['type' => 'follow_up', 'note' => 'Called the resident to submit a valid ID.']);
    }

    public function test_update_needs_a_note_or_a_new_status(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/service-requests/{$serviceRequest->id}/follow-ups", ['action_date' => today()->toDateString(), 'note' => ''])
            ->assertSessionHasErrorsIn('addUpdate', 'note');

        $this->assertSame(0, $serviceRequest->updates()->count());
    }

    public function test_update_rejects_the_current_status(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/service-requests/{$serviceRequest->id}/follow-ups", ['action_date' => today()->toDateString(), 'status' => 'pending'])
            ->assertSessionHasErrorsIn('addUpdate', 'status');
    }

    public function test_new_resident_can_be_added_while_creating_a_request(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/service-requests', [
                ...$this->validPayload(Resident::factory()->create()),
                'resident_id' => '',
                'new_resident' => '1',
                'resident' => ['name' => 'Ana Lim', 'address' => '3 Ilang-Ilang St.', 'contact_number' => '', 'purok' => 'Purok 6'],
            ])
            ->assertRedirect();

        $resident = Resident::where('name', 'Ana Lim')->firstOrFail();
        $this->assertSame('Purok 6', $resident->purok);
        $this->assertDatabaseHas('service_requests', ['resident_id' => $resident->id]);
    }

    public function test_new_resident_fields_are_validated_with_the_request(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/service-requests', [
                'new_resident' => '1',
                'resident' => ['name' => '', 'address' => '', 'purok' => ''],
                'category' => '',
            ])
            ->assertSessionHasErrorsIn('createRequest', ['resident.name', 'resident.address', 'resident.purok', 'category'])
            ->assertSessionDoesntHaveErrors('resident_id', null, 'createRequest');

        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_requests_can_be_filtered_by_status_category_and_search(): void
    {
        $maria = Resident::factory()->create(['name' => 'Maria Santos']);
        $juan = Resident::factory()->create(['name' => 'Juan dela Cruz']);
        ServiceRequest::factory()->for($maria)->status(RequestStatus::Completed)->create(['category' => RequestCategory::CertificateClearance]);
        ServiceRequest::factory()->for($juan)->create(['category' => RequestCategory::AssistanceReferral]);

        $user = User::factory()->create();

        $this->assertListedResidents($user, '/service-requests?status=completed', ['Maria Santos']);
        $this->assertListedResidents($user, '/service-requests?category=assistance_referral', ['Juan dela Cruz']);
        $this->assertListedResidents($user, '/service-requests?search=Maria', ['Maria Santos']);
    }

    public function test_new_request_form_is_not_prefilled_from_the_list(): void
    {
        ServiceRequest::factory()->create(['description' => 'Existing request text']);

        $this->actingAs(User::factory()->create())->get('/service-requests')
            ->assertSeeInOrder(['Existing request text', 'New Service Request'])
            ->assertDontSee('required>Existing request text</textarea>', false);
    }

    /**
     * @param  list<string>  $expectedNames
     */
    private function assertListedResidents(User $user, string $url, array $expectedNames): void
    {
        $this->actingAs($user)->get($url)->assertViewHas(
            'serviceRequests',
            fn ($serviceRequests) => $serviceRequests->pluck('resident.name')->all() === $expectedNames,
        );
    }

    public function test_details_page_shows_request_history(): void
    {
        $user = User::factory()->create();
        $serviceRequest = ServiceRequest::record([
            'resident_id' => Resident::factory()->create()->id,
            'category' => 'complaint',
            'description' => 'Illegal dumping of garbage.',
            'document_status' => 'not_required',
            'date_received' => today()->toDateString(),
        ], $user);

        $this->actingAs($user)->get("/service-requests/{$serviceRequest->id}")
            ->assertOk()
            ->assertSee($serviceRequest->reference_no)
            ->assertSee('Illegal dumping of garbage.')
            ->assertSee('Request recorded as');
    }
}
