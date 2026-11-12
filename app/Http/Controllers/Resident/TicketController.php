<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Resident Support Tickets (FR-13, Screen 5): asynchronous, no live chat.
 */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $resident = $request->user()->resident;
        $tickets = $resident->tickets()->with('serviceRequest')->latest('updated_at')->latest('id')->get();

        $selected = $tickets->firstWhere('id', (int) $request->query('ticket')) ?? $tickets->first();
        $selected?->load('messages.user');

        return view('resident.tickets.index', [
            'tickets' => $tickets,
            'selected' => $selected,
            'ownRequests' => $resident->serviceRequests()->with('serviceType')->latest('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resident = $request->user()->resident;

        $validated = $request->validateWithBag('newTicket', [
            'subject' => ['required', 'string', 'max:150'],
            'service_request_id' => ['nullable', 'integer', Rule::exists('service_requests', 'id')->where('resident_id', $resident->id)],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'service_request_id.exists' => 'Choose one of your own requests, or leave it blank.',
        ]);

        $ticket = Ticket::open($resident, $request->user(), $validated['subject'], $validated['message'], $validated['service_request_id'] ?? null);

        return redirect()->route('resident.tickets.index', ['ticket' => $ticket->id])
            ->with('success', "Ticket {$ticket->ticket_no} sent. Staff reply during office hours.");
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        Gate::authorize('view-ticket', $ticket);
        abort_if($ticket->isClosed(), 422, 'This ticket is closed.');

        $validated = $request->validateWithBag('reply', [
            'message' => ['required', 'string', 'max:2000'],
        ], ['message.required' => 'Please type your message before sending.']);

        $ticket->reply($request->user(), $validated['message']);

        return redirect()->route('resident.tickets.index', ['ticket' => $ticket->id])
            ->with('success', 'Reply sent. The ticket is open again for Staff.');
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('view-ticket', $ticket);
        $ticket->close();

        return redirect()->route('resident.tickets.index', ['ticket' => $ticket->id])
            ->with('success', "Ticket {$ticket->ticket_no} is closed.");
    }
}
