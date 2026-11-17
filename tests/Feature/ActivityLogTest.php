<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\ActivityAction;
use App\Enums\RequestStatus;
use App\Models\ActivityLog;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Activity Log: every Staff and Administrator change is recorded with who, what, when, IP, and
 * before → after values; only the Administrator can read it, and nobody can change it.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_change_records_who_what_and_before_after(): void
    {
        $staff = User::factory()->create(['name' => 'Sec. Cruz']);
        $serviceRequest = ServiceRequest::factory()->status(RequestStatus::UnderReview)->create();

        $this->actingAs($staff)
            ->patch("/service-requests/{$serviceRequest->id}/status", ['status' => 'approved'], ['REMOTE_ADDR' => '192.168.1.50'])
            ->assertRedirect();

        $entry = ActivityLog::sole();
        $this->assertSame(ActivityAction::RequestStatusChanged, $entry->action);
        $this->assertSame($staff->id, $entry->user_id);
        $this->assertSame('Sec. Cruz', $entry->user_name);
        $this->assertSame($serviceRequest->reference_no, $entry->subject_label);
        $this->assertTrue($entry->subject->is($serviceRequest));
        $this->assertSame(['Status' => ['Under Review', 'Approved']], $entry->changes);
        $this->assertSame('192.168.1.50', $entry->ip_address);
    }

    public function test_rejected_status_change_is_not_recorded(): void
    {
        $serviceRequest = ServiceRequest::factory()->status(RequestStatus::Submitted)->create();

        $this->actingAs(User::factory()->create())
            ->patch("/service-requests/{$serviceRequest->id}/status", ['status' => 'completed'])
            ->assertSessionHasErrorsIn('updateStatus', 'status');

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_administrator_actions_are_recorded_too(): void
    {
        $administrator = User::factory()->administrator()->create();
        $serviceRequest = ServiceRequest::factory()->status(RequestStatus::CashPayment)->create();

        $this->actingAs($administrator)->post("/service-requests/{$serviceRequest->id}/complete")->assertRedirect();

        $entry = ActivityLog::sole();
        $this->assertSame(ActivityAction::RequestCompleted, $entry->action);
        $this->assertSame($administrator->id, $entry->user_id);
        $this->assertSame(['Status' => ['Cash Payment', 'Completed']], $entry->changes);
    }

    public function test_fee_and_checklist_edits_show_before_and_after(): void
    {
        $serviceType = ServiceType::factory()->withDocuments(['Valid ID'])->create(['fee' => '30.00']);
        $requirement = $serviceType->documentRequirements->first();

        $this->actingAs(User::factory()->administrator()->create())->put("/admin/service-types/{$serviceType->id}", [
            'name' => $serviceType->name,
            'category' => $serviceType->category->value,
            'fee' => '50.00',
            'documents' => [
                ['id' => $requirement->id, 'name' => 'Valid ID', 'required' => '1'],
                ['name' => 'Cedula', 'required' => '0'],
            ],
        ])->assertRedirect();

        $changes = ActivityLog::sole()->changes;
        $this->assertSame(['₱30.00', '₱50.00'], $changes['Fee']);
        $this->assertSame(['Valid ID (required)', 'Valid ID (required), Cedula (optional)'], $changes['Checklist']);
        $this->assertArrayNotHasKey('Name', $changes);
    }

    public function test_verification_rejection_records_the_reason(): void
    {
        $pending = Resident::factory()->pending()->withAccount(AccountStatus::Pending)->create();

        $this->actingAs(User::factory()->create())
            ->post("/residents/{$pending->id}/reject", ['rejection_reason' => 'ID photo is unreadable'])
            ->assertRedirect();

        $entry = ActivityLog::sole();
        $this->assertSame(ActivityAction::ResidentRejected, $entry->action);
        $this->assertSame(['Pending', 'Rejected'], $entry->changes['Verification']);
        $this->assertSame([null, 'ID photo is unreadable'], $entry->changes['Reason']);
    }

    public function test_password_reset_never_records_the_password(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs(User::factory()->administrator()->create())
            ->post("/admin/users/{$staff->id}/reset-password")
            ->assertRedirect();

        $temporaryPassword = $response->getSession()->get('temporaryPassword')['password'];
        $entry = ActivityLog::sole();
        $this->assertSame(ActivityAction::PasswordReset, $entry->action);
        $this->assertStringNotContainsString($temporaryPassword, json_encode($entry->toArray()));
    }

    public function test_residents_own_actions_are_not_recorded(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $ticket = Ticket::open($resident, $resident->user, 'Question', 'Hello');

        $this->actingAs($resident->user)->post("/my/tickets/{$ticket->id}/close")->assertRedirect();
        $this->actingAs($resident->user)->post("/my/tickets/{$ticket->id}/reply", ['message' => 'x']);

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_staff_ticket_reply_is_recorded(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $ticket = Ticket::open($resident, $resident->user, 'Fee question', 'How much?');

        $this->actingAs(User::factory()->create())->post("/tickets/{$ticket->id}/reply", ['message' => 'It is ₱50.00.'])->assertRedirect();

        $this->assertSame(['Status' => ['Open', 'Answered']], ActivityLog::sole()->changes);
    }

    public function test_only_the_administrator_can_see_or_export_the_log(): void
    {
        $staff = User::factory()->create();
        $resident = Resident::factory()->withAccount()->create();

        $this->actingAs($staff)->get('/admin/activity-log')->assertForbidden();
        $this->actingAs($staff)->get('/admin/activity-log/export')->assertForbidden();
        $this->actingAs($resident->user)->get('/admin/activity-log')->assertForbidden();
        $this->actingAs($staff)->get('/dashboard')->assertDontSee('Activity Log');

        $this->actingAs(User::factory()->administrator()->create())->get('/admin/activity-log')->assertOk();
    }

    public function test_log_filters_by_user_action_and_search(): void
    {
        $cruz = User::factory()->create(['name' => 'Sec. Cruz']);
        $lim = User::factory()->create(['name' => 'Ana Lim']);
        ActivityLog::factory()->for($cruz)->create(['user_name' => 'Sec. Cruz', 'subject_label' => 'SR-2026-0005', 'description' => 'Added a note to SR-2026-0005.']);
        ActivityLog::factory()->for($lim)->create([
            'user_name' => 'Ana Lim', 'action' => ActivityAction::TicketClosed, 'subject_label' => 'TK-2026-0002', 'description' => 'Closed TK-2026-0002.',
        ]);
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)->get("/admin/activity-log?user={$cruz->id}")
            ->assertOk()->assertSee('SR-2026-0005')->assertDontSee('TK-2026-0002');
        $this->actingAs($administrator)->get('/admin/activity-log?action=ticket_closed')
            ->assertSee('TK-2026-0002')->assertDontSee('SR-2026-0005');
        $this->actingAs($administrator)->get('/admin/activity-log?search=0005')
            ->assertSee('SR-2026-0005')->assertDontSee('TK-2026-0002');
    }

    public function test_csv_export_contains_the_filtered_entries(): void
    {
        ActivityLog::factory()->create([
            'user_name' => 'Sec. Cruz', 'action' => ActivityAction::RequestStatusChanged, 'subject_label' => 'SR-2026-0005',
            'description' => 'Moved SR-2026-0005 from Under Review to Approved.', 'changes' => ['Status' => ['Under Review', 'Approved']],
        ]);
        ActivityLog::factory()->create(['subject_label' => 'SR-2026-0009', 'description' => 'Added a note to SR-2026-0009.']);

        $response = $this->actingAs(User::factory()->administrator()->create())
            ->get('/admin/activity-log/export?search=0005')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Status: Under Review → Approved', $csv);
        $this->assertStringContainsString('Sec. Cruz', $csv);
        $this->assertStringNotContainsString('SR-2026-0009', $csv);
    }

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $entry = ActivityLog::factory()->create();

        try {
            $entry->update(['description' => 'Tampered']);
            $this->fail('Updating an entry should not be allowed.');
        } catch (LogicException) {
        }

        try {
            $entry->delete();
            $this->fail('Deleting an entry should not be allowed.');
        } catch (LogicException) {
        }

        $this->assertSame('Added a follow-up note.', $entry->fresh()->description);
    }
}
