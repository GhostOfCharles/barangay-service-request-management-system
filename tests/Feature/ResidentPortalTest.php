<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FR-02, FR-10, FR-11: a verified resident submits requests and sees only his or her own,
 * with the reference number, status tracker, and amount due.
 */
class ResidentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_submits_a_request_with_the_checklist(): void
    {
        Storage::fake('local');
        $resident = Resident::factory()->withAccount()->create();
        $serviceType = ServiceType::factory()->withDocuments(['Valid ID (front)'])->create();
        $requirement = $serviceType->documentRequirements->first();

        $this->actingAs($resident->user)->post('/my/requests', [
            'service_type_id' => $serviceType->id,
            'purpose' => 'Employment requirement',
            'documents' => [$requirement->id => UploadedFile::fake()->image('id.png')],
        ])->assertRedirect()->assertSessionHas('success');

        $serviceRequest = ServiceRequest::firstOrFail();
        $this->assertSame($resident->id, $serviceRequest->resident_id);
        $this->assertSame($resident->user_id, $serviceRequest->created_by);
        $this->assertTrue($serviceRequest->received_at->isToday());
    }

    public function test_inactive_service_types_are_not_offered_or_accepted(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $inactive = ServiceType::factory()->create(['name' => 'Old Permit', 'is_active' => false]);

        $this->actingAs($resident->user)->get('/my/requests/new')->assertDontSee('Old Permit');
        $this->actingAs($resident->user)->post('/my/requests', ['service_type_id' => $inactive->id, 'purpose' => 'x'])
            ->assertSessionHasErrorsIn('submitRequest', 'service_type_id');
    }

    public function test_dashboard_shows_only_own_requests_and_the_amount_due(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $mine = ServiceRequest::factory()->for($resident)->status(RequestStatus::CashPayment)->create(['fee_amount' => '50.00']);
        ServiceRequest::factory()->for($resident)->status(RequestStatus::Completed)->create(['fee_amount' => '30.00']);
        $someoneElses = ServiceRequest::factory()->create();

        $this->actingAs($resident->user)->get('/my')
            ->assertOk()
            ->assertSee($mine->reference_no)
            ->assertDontSee($someoneElses->reference_no)
            ->assertViewHas('amountDue', 50.0)
            ->assertSeeText('pay ₱50.00 in cash upon receiving your document');
    }

    public function test_resident_cannot_open_another_residents_documents(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('docs/a.pdf', 'pdf');
        $owner = Resident::factory()->withAccount()->create();
        $otherResident = Resident::factory()->withAccount()->create();
        $document = ServiceRequest::factory()->for($owner)->create()
            ->documents()->create(['document_name' => 'Valid ID', 'file_path' => 'docs/a.pdf', 'original_name' => 'a.pdf', 'file_size' => 3]);

        $this->actingAs($owner->user)->get("/files/documents/{$document->id}")->assertOk();
        $this->actingAs($otherResident->user)->get("/files/documents/{$document->id}")->assertForbidden();
    }
}
