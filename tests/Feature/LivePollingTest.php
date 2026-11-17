<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\RequestStatus;
use App\Enums\TicketStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Page polling (resources/js/live-poll.js): a GET of the current page with an X-Live-Regions
 * header returns just those Blade fragments, under the page's own permission checks.
 */
class LivePollingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<string>  $regions
     */
    private function poll(string $uri, array $regions): TestResponse
    {
        return $this->getJson($uri, ['X-Live-Regions' => implode(',', $regions)]);
    }

    public function test_guests_are_told_to_stop(): void
    {
        $this->poll('/dashboard', ['dashboard-status'])
            ->assertUnauthorized()
            ->assertJson(['stop' => true]);
    }

    public function test_residents_cannot_poll_personnel_pages(): void
    {
        $resident = Resident::factory()->withAccount()->create();

        foreach (['/dashboard', '/service-requests', '/residents', '/tickets'] as $uri) {
            $this->actingAs($resident->user)->poll($uri, ['dashboard-status'])
                ->assertForbidden()
                ->assertJson(['stop' => true]);
        }
    }

    public function test_a_deactivated_account_is_logged_out_and_told_to_stop(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff);
        $staff->update(['status' => AccountStatus::Inactive]);

        $this->poll('/dashboard', ['dashboard-status'])->assertUnauthorized()->assertJson(['stop' => true]);
        $this->assertGuest();
    }

    public function test_resident_dashboard_returns_only_the_requested_regions_with_own_data(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $mine = ServiceRequest::factory()->for($resident)->status(RequestStatus::CashPayment)->create(['fee_amount' => '75.00']);
        $someoneElses = ServiceRequest::factory()->create();

        $response = $this->actingAs($resident->user)
            ->poll('/my', ['resident-stats', 'resident-requests', "track-body-{$mine->id}", "track-body-{$someoneElses->id}"])
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $regions = $response->json('regions');
        $this->assertEqualsCanonicalizing(['resident-stats', 'resident-requests', "track-body-{$mine->id}"], array_keys($regions));
        $this->assertStringContainsString('data-live="resident-requests"', $regions['resident-requests']);
        $this->assertStringContainsString($mine->reference_no, $regions['resident-requests']);
        $this->assertStringContainsString('₱75.00', $regions['resident-stats']);
        $this->assertStringNotContainsString($someoneElses->reference_no, $response->getContent());
    }

    public function test_resident_cannot_poll_another_residents_ticket(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $otherResident = Resident::factory()->withAccount()->create();
        Ticket::open($resident, $resident->user, 'My own question', 'Hello');
        $otherTicket = Ticket::open($otherResident, $otherResident->user, 'Private matter', 'Secret details');

        $response = $this->actingAs($resident->user)
            ->poll("/my/tickets?ticket={$otherTicket->id}", ['ticket-list', 'thread-head', 'thread-messages'])
            ->assertOk();

        $this->assertStringContainsString('My own question', $response->getContent());
        $this->assertStringNotContainsString('Private matter', $response->getContent());
        $this->assertStringNotContainsString('Secret details', $response->getContent());
    }

    public function test_request_details_reflect_another_users_status_change(): void
    {
        $staff = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->status(RequestStatus::UnderReview)->create();
        $regions = ['request-header', 'request-actions', 'request-action-bar', 'request-log'];

        $this->actingAs($staff)->poll("/service-requests/{$serviceRequest->id}", $regions)
            ->assertOk()
            ->assertJsonPath('regions.request-action-bar', fn (string $html) => str_contains($html, 'Move to Approved'));

        $serviceRequest->moveTo(RequestStatus::Approved, User::factory()->create(['name' => 'Other Clerk']));

        $response = $this->actingAs($staff)->poll("/service-requests/{$serviceRequest->id}", $regions)->assertOk();
        $this->assertStringContainsString('Move to Cash Payment', $response->json('regions.request-action-bar'));
        $this->assertStringContainsString('Approved', $response->json('regions.request-header'));
        $this->assertStringContainsString('Other Clerk', $response->json('regions.request-log'));
    }

    public function test_staff_inbox_shows_a_residents_new_reply(): void
    {
        $staff = User::factory()->create();
        $resident = Resident::factory()->withAccount()->create();
        $ticket = Ticket::open($resident, $resident->user, 'Fee question', 'How much is it?');

        $ticket->reply($resident->user, 'Also, when can I pick it up?');

        $this->actingAs($staff)->poll("/tickets?ticket={$ticket->id}", ['thread-messages', 'ticket-tabs'])
            ->assertOk()
            ->assertJsonPath('regions.thread-messages', fn (string $html) => str_contains($html, 'when can I pick it up?'))
            ->assertJsonPath('regions.ticket-tabs', fn (string $html) => str_contains($html, 'Open (1)'));
    }

    public function test_verify_panel_drops_its_actions_once_another_user_decides(): void
    {
        $staff = User::factory()->create();
        $pending = Resident::factory()->pending()->withAccount(AccountStatus::Pending)->create();
        $uri = "/residents?verification=pending&review={$pending->id}";

        $this->actingAs($staff)->poll($uri, ['resident-verify', 'resident-results'])
            ->assertOk()
            ->assertJsonPath('regions.resident-verify', fn (string $html) => str_contains($html, 'Approve &amp; Activate'));

        $this->actingAs(User::factory()->administrator()->create(['name' => 'Admin Reyes']))
            ->post("/residents/{$pending->id}/approve")
            ->assertRedirect();

        $response = $this->actingAs($staff)->poll($uri, ['resident-verify', 'resident-results'])->assertOk();
        $this->assertStringNotContainsString('Approve &amp; Activate', $response->json('regions.resident-verify'));
        $this->assertStringContainsString('already verified by Admin Reyes', $response->json('regions.resident-verify'));
        $this->assertStringNotContainsString($pending->full_name, $response->json('regions.resident-results'));
    }

    public function test_polling_never_changes_records(): void
    {
        $staff = User::factory()->create();
        $resident = Resident::factory()->withAccount()->create();
        $serviceRequest = ServiceRequest::factory()->for($resident)->status(RequestStatus::Submitted)->create();
        $ticket = Ticket::open($resident, $resident->user, 'Question', 'Hello');
        $before = [$serviceRequest->fresh()->toArray(), $ticket->fresh()->toArray(), $serviceRequest->logs()->count()];

        $this->actingAs($staff)->poll("/service-requests/{$serviceRequest->id}", ['request-actions'])->assertOk();
        $this->actingAs($staff)->poll("/tickets?ticket={$ticket->id}", ['thread-messages'])->assertOk();
        $this->actingAs($resident->user)->poll('/my', ['resident-requests'])->assertOk();

        $this->assertSame($before, [$serviceRequest->fresh()->toArray(), $ticket->fresh()->toArray(), $serviceRequest->logs()->count()]);
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_normal_page_loads_are_unchanged(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('data-live="dashboard-status"', false);
    }
}
