{{-- One ticket's conversation, reply box, and Close Ticket (Screens 5 and 12). Expects $ticket, $replyRoute, $closeRoute, $note, $backRoute. --}}
<a href="{{ $backRoute }}" class="thread-back d-lg-none"><i class="bi bi-arrow-left"></i> All tickets</a>
<div class="panel p-3 h-100" id="thread">
    @fragment('thread-head')
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3" data-live="thread-head">
        <h2 class="h6 fw-bold mb-0">{{ $ticket->ticket_no }} — {{ $ticket->subject }}</h2>
        <x-status-badge :status="$ticket->status" kind="ticket" />
        @if ($ticket->serviceRequest)
            <span class="small text-secondary">Linked: {{ $ticket->serviceRequest->reference_no }}</span>
        @endif
    </div>
    @endfragment

    @fragment('thread-messages')
    <div data-live="thread-messages" aria-live="polite">
    @foreach ($ticket->messages as $entry)
        @php($mine = $entry->user_id === auth()->id())
        <div @class(['message', 'is-mine' => $mine])>
            <div class="message-meta">
                {{ $mine ? 'You' : $entry->user->name.($entry->user->isPersonnel() ? ' ('.$entry->user->role->label().')' : '') }}
                · {{ $entry->created_at->format('M j, Y g:i A') }}
            </div>
            <div class="message-body">{{ $entry->message }}</div>
        </div>
    @endforeach
    </div>
    @endfragment

    {{-- Kept as is while a reply is being typed; refreshes once the box is empty again. --}}
    @fragment('thread-actions')
    <div data-live="thread-actions">
    @if ($ticket->isClosed())
        <p class="text-secondary small mb-0">This ticket is closed. Open a new ticket if you need more help.</p>
    @else
        <form method="POST" action="{{ $replyRoute }}" class="mt-3" novalidate>
            @csrf
            <label for="reply-message" class="form-label">Reply</label>
            <textarea id="reply-message" name="message" rows="3" required
                      class="form-control @error('message', 'reply') is-invalid @enderror"
                      placeholder="Type your message…">{{ old('message') }}</textarea>
            @error('message', 'reply')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="thread-actions d-flex flex-wrap gap-2 mt-2">
                <button type="submit" class="btn btn-success">Send Reply</button>
                <button type="button" class="btn btn-outline-secondary bg-white" data-bs-toggle="modal" data-bs-target="#closeTicketModal">Close Ticket</button>
            </div>
        </form>
        <form method="POST" action="{{ $closeRoute }}" id="close-ticket-form" class="d-none">@csrf</form>
        <p class="small text-secondary mt-2 mb-0">{{ $note }}</p>

        @push('modals')
            <div class="modal fade modal-sheet" id="closeTicketModal" tabindex="-1" aria-labelledby="closeTicketModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body p-4">
                            <h2 class="h5 fw-bold mb-2" id="closeTicketModalLabel">Close {{ $ticket->ticket_no }}?</h2>
                            <p class="text-secondary mb-4">A closed ticket can't take new replies. Open a new ticket if the concern comes back.</p>
                            <div class="sheet-actions d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" form="close-ticket-form" class="btn btn-success">Close Ticket</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endpush
    @endif
    </div>
    @endfragment
</div>
