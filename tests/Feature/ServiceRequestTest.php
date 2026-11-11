<?php

namespace Tests\Feature;

use App\Enums\LogEntryType;
use App\Enums\RequestStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FR-02, FR-04, FR-05, FR-06, FR-10, FR-12: submission with the document checklist,
 * the one status workflow, follow-up notes, and search.
 */
class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function clearance(): ServiceType
    {
        return ServiceType::factory()->withDocuments(['Valid ID (front)', 'Proof of Residency'])->create(['name' => 'Barangay Clearance', 'fee' => '50.00']);
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function filesFor(ServiceType $serviceType): array
    {
        return $serviceType->documentRequirements->mapWithKeys(fn ($requirement) => [
            $requirement->id => UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf'),
        ])->all();
    }

    public function test_staff_assisted_request_gets_a_reference_documents_and_first_log_entry(): void
    {
        Storage::fake('local');
        $serviceType = $this->clearance();
        $resident = Resident::factory()->create();
        $staff = User::factory()->create();

        $this->actingAs($staff)->post('/service-requests', [
            'resident_id' => $resident->id,
            'service_type_id' => $serviceType->id,
            'purpose' => 'Employment requirement',
            'received_at' => today()->toDateString(),
            'documents' => $this->filesFor($serviceType),
        ])->assertRedirect();

        $serviceRequest = ServiceRequest::firstOrFail();
        $this->assertSame('SR-'.now()->year.'-0001', $serviceRequest->reference_no);
        $this->assertSame(RequestStatus::Submitted, $serviceRequest->status);
        $this->assertSame('50.00', $serviceRequest->fee_amount);
        $this->assertCount(2, $serviceRequest->documents);
        Storage::disk('local')->assertExists($serviceRequest->documents->first()->file_path);
        $this->assertSame(RequestStatus::Submitted, $serviceRequest->logs()->first()->new_status);
    }

    public function test_request_cannot_be_submitted_without_every_required_document(): void
    {
        Storage::fake('local');
        $serviceType = $this->clearance();
        $validId = $serviceType->documentRequirements->first();
        $proof = $serviceType->documentRequirements->last();

        $this->actingAs(User::factory()->create())->post('/service-requests', [
            'resident_id' => Resident::factory()->create()->id,
            'service_type_id' => $serviceType->id,
            'purpose' => 'Employment requirement',
            'received_at' => today()->toDateString(),
            'documents' => [$validId->id => UploadedFile::fake()->create('id.exe', 10)],
        ])->assertSessionHasErrorsIn('createRequest', [
            "documents.{$validId->id}",
            "documents.{$proof->id}" => 'Please attach the Proof of Residency.',
        ]);

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_status_moves_one_step_at_a_time_and_is_logged(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();
        $staff = User::factory()->create();

        $this->actingAs($staff)->patch("/service-requests/{$serviceRequest->id}/status", ['status' => 'approved'])
            ->assertSessionHasErrorsIn('updateStatus', 'status');

        $this->actingAs($staff)->patch("/service-requests/{$serviceRequest->id}/status", ['status' => 'under_review'])
            ->assertSessionHasNoErrors();

        $this->assertSame(RequestStatus::UnderReview, $serviceRequest->fresh()->status);
        $this->assertDatabaseHas('request_logs', [
            'service_request_id' => $serviceRequest->id,
            'user_id' => $staff->id,
            'old_status' => 'submitted',
            'new_status' => 'under_review',
        ]);
    }

    public function test_staff_go_up_to_cash_payment_and_only_the_administrator_completes(): void
    {
        $serviceRequest = ServiceRequest::factory()->status(RequestStatus::CashPayment)->create();
        $staff = User::factory()->create();
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($staff)->patch("/service-requests/{$serviceRequest->id}/status", ['status' => 'completed'])
            ->assertSessionHasErrorsIn('updateStatus', ['status' => 'Only the Administrator can set Completed.']);
        $this->actingAs($staff)->post("/service-requests/{$serviceRequest->id}/complete")->assertForbidden();

        $this->actingAs($administrator)->post("/service-requests/{$serviceRequest->id}/complete")->assertRedirect();

        $serviceRequest->refresh();
        $this->assertSame(RequestStatus::Completed, $serviceRequest->status);
        $this->assertSame($administrator->id, $serviceRequest->completed_by);
        $this->assertSame('0.00', $serviceRequest->amountDue());
    }

    public function test_free_services_skip_cash_payment(): void
    {
        $serviceRequest = ServiceRequest::factory()->free()->status(RequestStatus::Approved)->create();

        $this->assertSame(RequestStatus::Completed, $serviceRequest->nextStatus());
        $this->assertNotContains(RequestStatus::CashPayment, array_column($serviceRequest->load('logs')->trackerSteps(), 'status'));

        $this->actingAs(User::factory()->create())->patch("/service-requests/{$serviceRequest->id}/status", ['status' => 'cash_payment'])
            ->assertSessionHasErrorsIn('updateStatus', 'status');
    }

    public function test_follow_up_note_is_added_to_the_request_log(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();

        $this->actingAs(User::factory()->create())->post("/service-requests/{$serviceRequest->id}/notes", ['note' => ''])
            ->assertSessionHasErrorsIn('addNote', 'note');

        $this->actingAs(User::factory()->create())->post("/service-requests/{$serviceRequest->id}/notes", ['note' => 'For captain\'s signature.']);

        $this->assertDatabaseHas('request_logs', ['entry_type' => LogEntryType::Note->value, 'note' => 'For captain\'s signature.']);
    }

    public function test_requests_can_be_searched_and_filtered(): void
    {
        $clearance = ServiceType::factory()->create(['name' => 'Barangay Clearance']);
        $maria = Resident::factory()->create(['full_name' => 'Maria Santos']);
        ServiceRequest::factory()->for($maria)->for($clearance)->status(RequestStatus::Completed)->create(['reference_no' => 'SR-2026-0001']);
        ServiceRequest::factory()->create();

        $user = User::factory()->create();

        foreach (['search=Santos', 'search=SR-2026-0001', "service_type={$clearance->id}", 'status=completed'] as $query) {
            $this->actingAs($user)->get("/service-requests?{$query}")
                ->assertViewHas('serviceRequests', fn ($requests) => $requests->pluck('reference_no')->all() === ['SR-2026-0001']);
        }
    }

    public function test_request_details_show_documents_tracker_and_log(): void
    {
        $serviceRequest = ServiceRequest::factory()->status(RequestStatus::CashPayment)->create();
        $serviceRequest->documents()->create(['document_name' => 'Valid ID (front)', 'file_path' => 'x.pdf', 'original_name' => 'id_front.pdf', 'file_size' => 1200]);

        $this->actingAs(User::factory()->create())->get("/service-requests/{$serviceRequest->id}")
            ->assertOk()
            ->assertSee($serviceRequest->reference_no)
            ->assertSee('Valid ID (front)')
            ->assertSee('Mark Completed')
            ->assertSee('Administrator only');
    }
}
