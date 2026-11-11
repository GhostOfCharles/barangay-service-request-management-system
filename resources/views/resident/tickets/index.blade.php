@extends('layouts.app')

@section('title', 'Support Tickets')
@section('reopenModal', $errors->newTicket->any() ? 'newTicketModal' : '')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="page-title">Support Tickets</h1>
            <p class="page-subtitle mb-0">Send a concern to barangay staff. Replies are not live; staff answer during office hours.</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                <i class="bi bi-plus-lg"></i> New Ticket
            </button>
        </div>
    </div>

    @if ($tickets->isEmpty())
        <div class="panel p-4 text-center text-secondary">
            You have no tickets. Use <strong>New Ticket</strong> to ask the barangay staff a question.
        </div>
    @else
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="ticket-list">
                    @foreach ($tickets as $ticket)
                        <a href="{{ route('resident.tickets.index', ['ticket' => $ticket->id]) }}"
                           @class(['ticket-card', 'is-selected' => $selected?->is($ticket)])
                           @if ($selected?->is($ticket)) aria-current="true" @endif>
                            <div class="ticket-card-title">{{ $ticket->ticket_no }} · {{ $ticket->subject }}</div>
                            <div class="ticket-card-meta">
                                <x-status-badge :status="$ticket->status" kind="ticket" />
                                @if ($ticket->serviceRequest)
                                    <span>Linked: {{ $ticket->serviceRequest->reference_no }}</span>
                                @else
                                    <span>{{ $ticket->created_at->format('M j, Y') }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-7">
                @if ($selected)
                    @include('tickets._thread', [
                        'ticket' => $selected,
                        'replyRoute' => route('resident.tickets.reply', $selected),
                        'closeRoute' => route('resident.tickets.close', $selected),
                        'note' => 'Replying sets the ticket back to Open for staff. Close it when your concern is resolved.',
                    ])
                @endif
            </div>
        </div>
    @endif
@endsection

@push('modals')
    <div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form method="POST" action="{{ route('resident.tickets.store') }}" novalidate>
                    @csrf
                    <div class="modal-body p-4">
                        <h2 class="h6 fw-bold mb-3" id="newTicketModalLabel">New Ticket</h2>

                        <div class="mb-3">
                            <label for="ticket-subject" class="form-label">Subject</label>
                            <input type="text" id="ticket-subject" name="subject" value="{{ old('subject') }}" required
                                   class="form-control @error('subject', 'newTicket') is-invalid @enderror" placeholder="e.g., Missing document">
                            @error('subject', 'newTicket')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="ticket-request" class="form-label">Linked request <span class="text-secondary">(optional)</span></label>
                            <select id="ticket-request" name="service_request_id" class="form-select @error('service_request_id', 'newTicket') is-invalid @enderror">
                                <option value="">None</option>
                                @foreach ($ownRequests as $ownRequest)
                                    <option value="{{ $ownRequest->id }}" @selected((string) old('service_request_id') === (string) $ownRequest->id)>
                                        {{ $ownRequest->reference_no }} · {{ $ownRequest->serviceType->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_request_id', 'newTicket')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="ticket-message" class="form-label">Message</label>
                            <textarea id="ticket-message" name="message" rows="4" required
                                      class="form-control @error('message', 'newTicket') is-invalid @enderror"
                                      placeholder="Describe your concern…">{{ old('message') }}</textarea>
                            @error('message', 'newTicket')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Send Ticket</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
