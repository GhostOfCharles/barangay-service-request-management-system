<?php

namespace Tests\Feature;

use App\Enums\RequestCategory;
use App\Enums\RequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_request_counts(): void
    {
        ServiceRequest::factory()->count(3)->create();
        ServiceRequest::factory()->count(2)->status(RequestStatus::InProgress)->create();
        ServiceRequest::factory()->status(RequestStatus::Completed)->create();

        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertViewHas('pendingCount', 3)
            ->assertViewHas('inProgressCount', 2)
            ->assertViewHas('completedTodayCount', 1)
            ->assertViewHas('residentCount', 6);
    }

    public function test_report_summarizes_filtered_requests(): void
    {
        ServiceRequest::factory()->create(['category' => RequestCategory::Complaint, 'date_received' => '2026-09-05']);
        ServiceRequest::factory()->status(RequestStatus::Completed)->create(['category' => RequestCategory::Complaint, 'date_received' => '2026-09-10']);
        ServiceRequest::factory()->create(['category' => RequestCategory::CertificateClearance, 'date_received' => '2026-09-12']);
        ServiceRequest::factory()->create(['category' => RequestCategory::Complaint, 'date_received' => '2026-10-01']);

        $response = $this->actingAs(User::factory()->create())
            ->get('/reports?date_from=2026-09-01&date_to=2026-09-30&category=complaint')
            ->assertOk()
            ->assertViewHas('totalCount', 2);

        $statusCounts = $response->viewData('statusCounts');
        $this->assertEquals(1, $statusCounts['pending']);
        $this->assertEquals(1, $statusCounts['completed']);
    }

    public function test_report_rejects_an_invalid_date_range(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/reports?date_from=2026-09-30&date_to=2026-09-01')
            ->assertSessionHasErrors('date_to');
    }
}
