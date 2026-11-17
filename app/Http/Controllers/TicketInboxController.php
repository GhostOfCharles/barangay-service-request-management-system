<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Enums\TicketStatus;
use App\Models\ActivityLog;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff Ticket Inbox (FR-13, Screen 12): read a ticket, reply (status becomes Answered), and close it.
 */
class TicketInboxController extends Controller
{
    public function index(Request $request): View
    {
        $status = TicketStatus::tryFrom((string) $request->query('status')) ?? TicketStatus::Open;

        $tickets = Ticket::with(['resident', 'serviceRequest'])
            ->where('status', $status)
            ->latest('updated_at')
            ->latest('id')
            ->get();

        $selected = $request->filled('ticket')
            ? Ticket::find($request->query('ticket'))
            : $tickets->first();
        $selected?->load(['messages.user', 'resident', 'serviceRequest']);

        return view('tickets.index', [
            'tickets' => $tickets,
            'selected' => $selected,
            'status' => $status,
            'counts' => Ticket::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_if($ticket->isClosed(), 422, 'This ticket is closed.');

        $validated = $request->validateWithBag('reply', [
            'message' => ['required', 'string', 'max:2000'],
        ], ['message.required' => 'Please type a reply before sending.']);

        $previous = $ticket->status;
        $ticket->reply($request->user(), $validated['message']);

        ActivityLog::record($request->user(), ActivityAction::TicketReplied,
            "Replied to {$ticket->ticket_no} ({$ticket->subject}).", $ticket,
            ActivityLog::differences(['Status' => $previous->label()], ['Status' => $ticket->status->label()]));

        return redirect()->route('tickets.index', ['status' => 'answered', 'ticket' => $ticket->id])
            ->with('success', "Reply sent. {$ticket->ticket_no} is now Answered.");
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $previous = $ticket->status;
        $ticket->close();

        ActivityLog::record($request->user(), ActivityAction::TicketClosed,
            "Closed {$ticket->ticket_no} ({$ticket->subject}).", $ticket,
            ActivityLog::differences(['Status' => $previous->label()], ['Status' => $ticket->status->label()]));

        return redirect()->route('tickets.index', ['status' => 'closed', 'ticket' => $ticket->id])
            ->with('success', "Ticket {$ticket->ticket_no} is closed.");
    }
}
