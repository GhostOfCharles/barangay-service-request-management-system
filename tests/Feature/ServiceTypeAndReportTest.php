<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-14 (service types, fees, checklists), FR-07 (staff dashboard), FR-08 (reports), FR-03 (residents).
 */
class ServiceTypeAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_adds_a_service_type_with_a_checklist(): void
    {
        $this->actingAs(User::factory()->administrator()->create())->post('/admin/service-types', [
            'name' => 'Indigency Certificate',
            'category' => 'certificate_clearance',
            'fee' => '0',
            'documents' => [
                ['name' => 'Valid ID (front)', 'required' => '1'],
                ['name' => '', 'required' => '1'],
                ['name' => 'Barangay ID', 'required' => '0'],
            ],
        ])->assertRedirect();

        $serviceType = ServiceType::where('name', 'Indigency Certificate')->firstOrFail();
        $this->assertSame('0.00', $serviceType->fee);
        $this->assertSame(['Valid ID (front)', 'Barangay ID'], $serviceType->documentRequirements->pluck('document_name')->all());
        $this->assertFalse($serviceType->documentRequirements->last()->is_required);
    }

    public function test_editing_the_checklist_removes_deleted_rows(): void
    {
        $serviceType = ServiceType::factory()->withDocuments(['Valid ID (front)', 'Cedula'])->create();
        $kept = $serviceType->documentRequirements->first();

        $this->actingAs(User::factory()->administrator()->create())->put("/admin/service-types/{$serviceType->id}", [
            'name' => $serviceType->name,
            'category' => 'certificate_clearance',
            'fee' => '75.50',
            'documents' => [['id' => $kept->id, 'name' => 'Valid ID (front)', 'required' => '1']],
        ]);

        $this->assertSame('75.50', $serviceType->fresh()->fee);
        $this->assertSame([$kept->id], $serviceType->documentRequirements()->pluck('id')->all());
    }

    public function test_negative_fee_is_rejected_and_staff_cannot_manage_service_types(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->post('/admin/service-types', ['name' => 'X', 'category' => 'complaint', 'fee' => '-5'])
            ->assertSessionHasErrors('fee');

        $this->actingAs(User::factory()->create())->get('/admin/service-types')->assertForbidden();
    }

    public function test_dashboard_counts_statuses_verifications_and_tickets(): void
    {
        ServiceRequest::factory()->count(2)->create();
        ServiceRequest::factory()->status(RequestStatus::CashPayment)->create();
        Resident::factory()->pending()->create();

        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertViewHas('statusCounts', fn ($counts) => $counts['submitted'] === 2 && $counts['cash_payment'] === 1)
            ->assertViewHas('pendingVerifications', 1);
    }

    public function test_report_totals_cash_collected_from_completed_requests_only(): void
    {
        $clearance = ServiceType::factory()->create(['name' => 'Barangay Clearance']);
        ServiceRequest::factory()->for($clearance)->status(RequestStatus::Completed)->create(['fee_amount' => '50.00', 'received_at' => '2026-09-05']);
        ServiceRequest::factory()->for($clearance)->status(RequestStatus::Completed)->create(['fee_amount' => '50.00', 'received_at' => '2026-09-10']);
        ServiceRequest::factory()->for($clearance)->status(RequestStatus::CashPayment)->create(['fee_amount' => '50.00', 'received_at' => '2026-09-12']);

        $response = $this->actingAs(User::factory()->create())->get('/reports?date_from=2026-09-01&date_to=2026-09-30')
            ->assertOk()
            ->assertViewHas('totalCount', 3);

        $row = $response->viewData('byServiceType')->first();
        $this->assertEquals(2, $row->completed);
        $this->assertEquals(100, $row->cash_collected);
    }

    public function test_staff_add_a_verified_resident_with_a_purok_from_the_list(): void
    {
        $this->actingAs(User::factory()->create())->post('/residents', [
            'full_name' => 'Santos, Maria',
            'address' => '12 Sampaguita St.',
            'purok' => 'Purok 3',
            'contact_number' => '09171234567',
        ])->assertRedirect();

        $this->assertDatabaseHas('residents', ['full_name' => 'Santos, Maria', 'verification_status' => VerificationStatus::Verified->value]);

        $this->actingAs(User::factory()->create())
            ->post('/residents', ['full_name' => 'X', 'address' => 'Y', 'purok' => 'Purok 99'])
            ->assertSessionHasErrorsIn('createResident', 'purok');
    }
}
