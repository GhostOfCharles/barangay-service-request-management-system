{{-- One ticket's conversation, reply box, and Close Ticket (Screens 5 and 12). Expects $ticket, $replyRoute, $closeRoute, $note. --}}
<div class="panel p-3 h-100">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h2 class="h6 fw-bold mb-0">{{ $ticket->ticket_no }} — {{ $ticket->subject }}</h2>
        <x-status-badge :status="$ticket->status" kind="ticket" />
        @if ($ticket->serviceRequest)
            <span class="small text-secondary">Linked: {{ $ticket->serviceRequest->reference_no }}</span>
        @endif
    </div>

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
            <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="submit" class="btn btn-success">Send Reply</button>
                <button type="submit" form="close-ticket-form" class="btn btn-outline-secondary bg-white">Close Ticket</button>
            </div>
        </form>
        <form method="POST" action="{{ $closeRoute }}" id="close-ticket-form" class="d-none">@csrf</form>
        <p class="small text-secondary mt-2 mb-0">{{ $note }}</p>
    @endif
</div>
