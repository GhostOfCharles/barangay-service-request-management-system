<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Resident;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-13: asynchronous issue tickets, Open > Answered > Closed.
 */
class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_opens_a_ticket_linked_to_own_request(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $request = ServiceRequest::factory()->for($resident)->create();

        $this->actingAs($resident->user)->post('/my/tickets', [
            'subject' => 'Missing document',
            'service_request_id' => $request->id,
            'message' => 'What should I attach?',
        ])->assertRedirect();

        $ticket = Ticket::firstOrFail();
        $this->assertSame('TK-'.now()->year.'-0001', $ticket->ticket_no);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame($request->id, $ticket->service_request_id);
        $this->assertCount(1, $ticket->messages);
    }

    public function test_resident_cannot_link_another_residents_request(): void
    {
        $resident = Resident::factory()->withAccount()->create();

        $this->actingAs($resident->user)->post('/my/tickets', [
            'subject' => 'Question',
            'service_request_id' => ServiceRequest::factory()->create()->id,
            'message' => 'Hello',
        ])->assertSessionHasErrorsIn('newTicket', 'service_request_id');
    }

    public function test_staff_reply_answers_and_resident_reply_reopens(): void
    {
        $resident = Resident::factory()->withAccount()->create();
        $ticket = Ticket::open($resident, $resident->user, 'Fee question', 'How much?');

        $this->actingAs(User::factory()->create())->post("/tickets/{$ticket->id}/reply", ['message' => 'Fifty pesos.']);
        $this->assertSame(TicketStatus::Answered, $ticket->fresh()->status);

        $this->actingAs($resident->user)->post("/my/tickets/{$ticket->id}/reply", ['message' => 'Paid where?']);
        $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);

        $this->actingAs($resident->user)->post("/my/tickets/{$ticket->id}/close");
        $this->assertSame(TicketStatus::Closed, $ticket->fresh()->status);
    }

    public function test_inbox_lists_tickets_by_status(): void
    {
        $open = Ticket::factory()->create(['subject' => 'Open one']);
        Ticket::factory()->create(['subject' => 'Closed one', 'status' => TicketStatus::Closed]);

        $this->actingAs(User::factory()->create())->get('/tickets')
            ->assertViewHas('tickets', fn ($tickets) => $tickets->pluck('id')->all() === [$open->id]);
    }

    public function test_resident_cannot_reply_to_another_residents_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $otherResident = Resident::factory()->withAccount()->create();

        $this->actingAs($otherResident->user)->post("/my/tickets/{$ticket->id}/reply", ['message' => 'Hi'])->assertForbidden();
    }
}
